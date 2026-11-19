const { abrirOpcionesCuenta } = require('./cuenta.cjs');
const { chromium, expect } = require("@playwright/test");
const { spawn, spawnSync } = require("node:child_process");
const fs = require("node:fs");
const path = require("node:path");
const net = require("node:net");
const crypto = require("node:crypto");

(async () => {
    const root = path.resolve(__dirname, "../..");
    const dir = path.join(
        root,
        "storage/framework/testing",
        `browser-${crypto.randomUUID()}`,
    );
    fs.mkdirSync(dir, { recursive: true });
    const db = path.join(dir, "browser.sqlite");
    fs.writeFileSync(db, "", { flag: "wx" });
    const socket = net.createServer();
    await new Promise((resolve) => socket.listen(0, "127.0.0.1", resolve));
    const port = socket.address().port;
    await new Promise((resolve) => socket.close(resolve));
    const base = `http://127.0.0.1:${port}`;
    const env = {
        ...process.env,
        APP_ENV: "testing",
        APP_KEY: "base64:" + crypto.randomBytes(32).toString("base64"),
        APP_DEBUG: "false",
        APP_URL: base,
        DB_CONNECTION: "sqlite",
        DB_DATABASE: db,
        DB_URL: "",
        CACHE_STORE: "array",
        SESSION_DRIVER: "database",
        SESSION_COOKIE: `tajibo_qa_${port}`,
        MAIL_MAILER: "array",
        AUTH_PASSWORD_RESET_ENABLED: "false",
        QUEUE_CONNECTION: "sync",
        TAJIBO_QA_DIR: dir,
        APP_CONFIG_CACHE: path.join(dir, "config.php"),
        BCRYPT_ROUNDS: "4",
    };
    const php = process.env.TAJIBO_PHP || "C:/wamp64/bin/php/php8.3.28/php.exe";
    const preparation = spawnSync(php, [path.join(__dirname, "preparar-cotizaciones.php")], {
        cwd: root,
        env,
        encoding: "utf8",
        windowsHide: true,
    });
    if (preparation.status !== 0)
        throw new Error(preparation.stderr || preparation.stdout);
    const credentials = JSON.parse(
        fs.readFileSync(path.join(dir, "credenciales.json"), "utf8"),
    );
    const server = spawn(
        php,
        [
            "-S",
            `127.0.0.1:${port}`,
            "-t",
            path.join(root, "public"),
            path.join(
                root,
                "vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php",
            ),
        ],
        {
            cwd: path.join(root, "public"),
            env,
            windowsHide: true,
            stdio: "ignore",
        },
    );
    let browser;
    const report = {
        baseDatos: "SQLite temporal aislada",
        motor: "Microsoft Edge / Playwright",
        comprobaciones: [],
        capturas: [],
    };
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/crear-pedido-2026-11-15");
    fs.mkdirSync(output, { recursive: true });
    try {
        let ready = false;
        for (let i = 0; i < 50; i++) {
            try {
                const response = await fetch(`${base}/login`);
                if (response.status === 200) {
                    ready = true;
                    break;
                }
            } catch {}
            await new Promise((resolve) => setTimeout(resolve, 150));
        }
        if (!ready) throw new Error("El servidor de pruebas no inició.");
        browser = await chromium.launch({ channel: "msedge", headless: true });
        const context = await browser.newContext({
            viewport: { width: 1440, height: 1000 },
            locale: "es-BO",
            reducedMotion: "reduce",
        });
        const page = await context.newPage();
        const errors = [];
        page.on("pageerror", (error) => errors.push(error.message));
        async function capture(name) {
            await page.screenshot({
                path: path.join(output, name + ".png"),
                fullPage: !name.startsWith("menu-"),
            });
            report.capturas.push(name + ".png");
        }
        async function noOverflow(label) {
            const widths = await page.evaluate(() => ({
                page: document.documentElement.scrollWidth,
                viewport: innerWidth,
            }));
            if (widths.page > widths.viewport)
                throw new Error(
                    `Desborde horizontal en ${label}: ${JSON.stringify(widths)}`,
                );
            report.comprobaciones.push(`${label}: sin desborde horizontal`);
        }
        async function login(role) {
            await page.goto(`${base}/login`);
            await page
                .getByLabel("Correo electrónico", { exact: true })
                .fill(`${role}@example.test`);
            await page
                .getByLabel("Contraseña", { exact: true })
                .fill(credentials.password);
            await page.getByRole("button", { name: "Entrar al panel" }).click();
            await expect(page).toHaveURL(`${base}/admin`);
            await expect(
                page.getByRole("heading", {
                    name: `Hola, ${role === "propietario" ? "Propietario" : "Maestro"} de prueba`,
                }),
            ).toBeVisible();
        }
        async function verificarCabecera(label) {
            const fondoCabecera = await page.locator('.form-themed > .card-header').first().evaluate(el=>getComputedStyle(el).backgroundImage);
            const fondoAccion = await page.locator('[data-cotizacion-form] button[type="submit"]').first().evaluate(el=>getComputedStyle(el).backgroundImage);
            expect(fondoCabecera).toBe(fondoAccion);
            report.comprobaciones.push(label+': cabecera con el mismo degradado que su acción');
        }
        await login('propietario');
        const datos=JSON.parse(fs.readFileSync(path.join(dir,'datos4a.json'),'utf8'));
        const hoy=new Intl.DateTimeFormat('en-CA',{timeZone:'America/La_Paz'}).format(new Date());
        async function cerrarAviso(){await expect(page.locator('.swal2-popup')).toBeVisible();await page.evaluate(()=>Swal.close());}
        async function cotizacionAprobada(){
            const token=await page.locator('[name="_token"]').first().inputValue();
            const response=await context.request.post(base+'/admin/cotizaciones/create',{data:{clave_registro:crypto.randomUUID(),fecha:hoy,detalles:[{
                tipo_mueble_id:datos.mesa,material_principal_id:datos.material,largo_cm:'120',ancho_cm:'60',alto_cm:'75',puertas:0,cajones:0,divisiones:0,acabado:'Barnizado natural',cantidad_muebles:1,mano_obra_unitaria:'20',otros_costos_total:'5',
                materiales:[{material_id:datos.material,componente:'General',cantidad_unitaria:'2',costo_unitario_estimado:'40',estado_costo:'conocido',fuente_costo:'referencia_catalogo'}]
            }]},headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},maxRedirects:0});
            expect(response.status(),await response.text()).toBe(302);
            const url=response.headers().location;
            await page.goto(url);await page.evaluate(()=>window.Swal?.close());
            const form=page.locator('form[action*="/aprobar"]');
            await form.locator('input[name^="precios["]').fill('150');
            await form.getByRole('button',{name:'Validar y aprobar',exact:true}).click();
            await cerrarAviso();
            return {url,id:new URL(url).pathname.split('/').pop()};
        }
        const cotizacion=await cotizacionAprobada();
        await page.getByRole('link',{name:'Crear pedido',exact:true}).click();
        await expect(page.locator('.page-heading-refined')).toBeVisible();
        await expect(page.locator('.pedido-create-total')).toContainText('Bs 150,00');
        await expect(page.locator('fieldset[data-cliente-nuevo]')).toBeVisible();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',cotizacion.url);
        for(const width of [1440,1280,1024,390]){
            await page.setViewportSize({width,height:1000});
            await noOverflow('Crear pedido '+width);
            const tabla=page.locator('.pedido-create-summary .table-responsive');
            expect(await tabla.evaluate(el=>el.scrollWidth<=el.clientWidth)).toBe(true);
            if(width===1440){
                const main=await page.locator('.pedido-create-main').boundingBox();
                const resumen=await page.locator('.pedido-create-summary').boundingBox();
                expect(Math.abs(main.y-resumen.y)).toBeLessThan(2);
                expect(resumen.x).toBeGreaterThan(main.x+main.width);
            }
            await capture('crear-cliente-nuevo-'+width);
        }
        await page.locator('[data-theme-toggle]').click();
        await noOverflow('Crear pedido oscuro móvil');await capture('crear-oscuro-390');
        await page.locator('[data-theme-toggle]').click();
        await page.setViewportSize({width:1440,height:1000});
        await page.locator('#cliente_id').selectOption(String(datos.cliente));
        await expect(page.locator('[data-cliente-nuevo]')).toBeHidden();
        await expect(page.locator('[data-cliente-nuevo]')).toHaveJSProperty('disabled',true);
        await capture('crear-cliente-existente');
        await page.locator('#cliente_id').selectOption('');
        await expect(page.locator('[data-cliente-nuevo]')).toBeVisible();
        await expect(page.locator('#cliente_nombre')).toBeEnabled();
        await page.locator('#cliente_id').selectOption(String(datos.cliente));
        await expect(page.locator('#fecha_prevista_entrega')).toHaveAttribute('min', hoy);
        await page.locator('#fecha_prevista_entrega').fill('2016-10-30');
        await page.locator('label[for="aceptacion"]').click();
        await page.locator('[data-crear-pedido]').evaluate(form => { form.noValidate = true; form.requestSubmit(); });
        await expect(page.locator('#fecha-entrega-error')).toHaveText('La entrega prevista debe ser hoy o una fecha posterior.');
        await expect(page.locator('#fecha_prevista_entrega')).toHaveValue('2016-10-30');
        await expect(page.locator('#cliente_id')).toHaveValue(String(datos.cliente));
        await expect(page.locator('.content-wrapper')).not.toContainText('validation.after_or_equal');
        await page.locator('#fecha_prevista_entrega').fill(hoy);
        await page.locator('label[for="aceptacion"]').click();
        report.comprobaciones.push('Fecha pasada: error traducido junto al campo, datos conservados y corrección a hoy');
        await page.locator('#responsable_id').selectOption({index:1});
        await page.locator('#observaciones').fill('Coordinar entrega del pedido de prueba.');
        await page.locator('label[for="aceptacion"]').click();
        await page.getByRole('button',{name:'Registrar pedido',exact:true}).click();
        await cerrarAviso();
        await expect(page).toHaveURL(/\/admin\/pedidos\/\d+$/);
        await expect(page.locator('.content-wrapper')).toContainText('Cliente de prueba 4A');
        await expect(page.locator('.content-wrapper')).toContainText('Bs 150,00');
        report.comprobaciones.push('Cliente existente: formulario nuevo se oculta/desactiva, cambio reversible y pedido registrado con precio aprobado');
        const pedidoUrl=page.url();
        await page.getByRole('link',{name:'Ver cotización',exact:true}).click();
        await expect(page.getByRole('link',{name:'Volver',exact:true})).toHaveAttribute('href',pedidoUrl);
        await page.getByRole('link',{name:'Volver',exact:true}).click();await expect(page).toHaveURL(pedidoUrl);
        await page.getByRole('link',{name:'Planificar materiales',exact:true}).click();
        await expect(page.getByRole('link',{name:'Volver al pedido',exact:true})).toHaveAttribute('href',pedidoUrl);
        await page.getByRole('link',{name:'Editar materiales',exact:true}).click();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',pedidoUrl);
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();
        await page.getByRole('link',{name:'Ver pagos',exact:true}).click();
        await page.getByRole('link',{name:'Nuevo pago',exact:true}).click();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',pedidoUrl);
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();await expect(page).toHaveURL(pedidoUrl);
        await page.getByRole('link',{name:'Registrar pago',exact:true}).click();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',pedidoUrl);
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();
        await page.goto(cotizacion.url);
        await expect(page.getByRole('link',{name:'Volver',exact:true})).toHaveAttribute('href',base+'/admin/cotizaciones');
        await page.goto(pedidoUrl);
        report.comprobaciones.push('Origen pedido: cotización, planificación y su edición, pagos y registrar pago regresan al detalle; cotización independiente vuelve al listado');
        for(const width of [1440,390]){
            await page.setViewportSize({width,height:1000});
            await noOverflow('Ver pedido '+width);await capture('ver-pedido-'+width);
        }
        await page.getByRole('link',{name:'Editar datos del pedido',exact:true}).click();
        await expect(page.locator('.pedidos-edit-layout')).toBeVisible();
        await page.locator('#observaciones').fill('Entrega coordinada con el cliente.');
        await noOverflow('Editar pedido móvil');await capture('editar-pedido-390');
        await page.setViewportSize({width:1440,height:1000});await capture('editar-pedido-1440');
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',pedidoUrl);
        await page.getByRole('button',{name:'Actualizar pedido',exact:true}).click();await cerrarAviso();
        await page.goto(pedidoUrl);
        await expect(page.locator('.pedidos-observaciones')).toHaveText('Entrega coordinada con el cliente.');
        await expect(page.locator('.pedido-create-total')).toContainText('Bs 150,00');
        await page.getByRole('link',{name:'Volver al listado',exact:true}).click();
        await page.locator('#q').fill('Cliente de prueba 4A');
        await page.getByRole('button',{name:'Buscar',exact:true}).click();
        await expect(page.locator('tbody')).toContainText('Cliente de prueba 4A');
        for(const width of [1440,390]){
            await page.setViewportSize({width,height:1000});
            await noOverflow('Listado pedidos '+width);await capture('listado-pedidos-'+width);
        }
        await page.getByRole('link',{name:'Limpiar',exact:true}).click();
        await expect(page.locator('#q')).toHaveValue('');
        await page.setViewportSize({width:1440,height:1000});
        report.comprobaciones.push('Pedidos: listado y filtro, consulta, edición y retorno conservados; precio invariable');
        await page.goto(pedidoUrl);
        await page.getByRole('button',{name:'Iniciar fabricación',exact:true}).click();
        await page.getByRole('button',{name:'Sí, iniciar',exact:true}).click();await cerrarAviso();
        await page.getByRole('link',{name:'Registrar materiales utilizados',exact:true}).click();
        await expect(page.locator('.uso-materiales-layout')).toBeVisible();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',pedidoUrl);
        await expect(page.locator('[data-uso-copiar]')).toHaveCount(0);
        await expect(page.locator('[data-uso-cantidad]').first()).toHaveValue('');
        await page.locator('[data-uso-cantidad]').first().fill('2.5');
        await expect(page.locator('[data-uso-cantidad]').first()).toHaveValue('2.5');
        await expect(page.locator('[data-uso-diferencia]').first()).toHaveText('+0,5');
        await page.getByRole('button',{name:'Añadir material utilizado',exact:true}).click();
        const adicional=page.locator('[data-uso-fila]').last();
        await adicional.locator('select').selectOption(String(datos.secundario));
        await adicional.locator('[data-uso-cantidad]').fill('0.75');
        for(const width of [1440,1024,390]){
            await page.setViewportSize({width,height:1000});await noOverflow('Registrar materiales '+width);await capture('registrar-materiales-'+width);
        }
        await page.locator('[data-theme-toggle]').click();await noOverflow('Registrar materiales oscuro móvil');await capture('registrar-materiales-oscuro-390');
        await page.locator('[data-theme-toggle]').click();
        await adicional.getByRole('button',{name:'Quitar fila nueva',exact:true}).click();
        await expect(page.locator('[data-uso-fila]')).toHaveCount(1);
        await page.getByRole('button',{name:'Guardar materiales',exact:true}).click();await cerrarAviso();
        await expect(page).toHaveURL(pedidoUrl);
        await expect(page.getByRole('region',{name:'Materiales utilizados del mueble 1',exact:true})).toContainText('2,5');
        await page.getByRole('link',{name:'Registrar materiales utilizados',exact:true}).click();
        await expect(page.locator('[data-uso-cantidad]').first()).toHaveValue('2.5');
        await page.locator('[data-uso-cantidad]').first().fill('2');
        await expect(page.locator('[data-uso-motivo]')).toHaveAttribute('required','');
        await page.locator('[data-uso-motivo]').fill('Corrección verificada durante la prueba aislada.');
        await page.getByRole('button',{name:'Guardar materiales',exact:true}).click();await cerrarAviso();
        await expect(page).toHaveURL(pedidoUrl);
        report.comprobaciones.push('Materiales utilizados: ingreso manual sin copia, diferencia, añadir/quitar, guardado, corrección justificada y retorno al pedido');
        const segunda=await cotizacionAprobada();
        await page.goto(base+'/admin/pedidos/create?cotizacion='+segunda.id);
        await page.locator('#cliente_nombre').fill('Ana Quispe de prueba');
        await page.locator('#cliente_telefono').fill('70000001');
        await page.locator('#cliente_email').fill('cliente-pedido@example.test');
        await page.locator('#cliente_direccion').fill('El Alto, dirección de prueba');
        await page.locator('label[for="aceptacion"]').click();
        await page.getByRole('button',{name:'Registrar pedido',exact:true}).click();
        await cerrarAviso();
        await expect(page).toHaveURL(/\/admin\/pedidos\/\d+$/);
        await expect(page.locator('.content-wrapper')).toContainText('Ana Quispe de prueba');
        report.comprobaciones.push('Cliente nuevo: registro del cliente y pedido desde el mismo formulario, conservando confirmación y precio');
        if(errors.length)throw Error(errors.join("; "));
        report.comprobaciones.push("Sin errores JavaScript");
        report.resultado="APROBADO";
        fs.writeFileSync(path.join(output,"resultado-browser.json"),JSON.stringify(report,null,2));
        console.log(JSON.stringify(report,null,2));
    } catch (error) {
        if (browser) {
            const pagina = browser.contexts()[0]?.pages()[0];
            if (pagina) {
                await pagina.screenshot({path:path.join(output,'error-formulario.png'),fullPage:true});
                fs.writeFileSync(path.join(output,'error-formulario.txt'), await pagina.locator('body').innerText());
            }
        }
        throw error;
    } finally {
        if (browser) await browser.close();
        server.kill();
        await new Promise((resolve) => server.once("exit", resolve));
        // Solo archivos temporales creados por esta ejecución; nunca bases/configuración reales.
        for (const file of [
            "credenciales.json",
            "datos4a.json",
            "casos5a.json",
            "browser.sqlite",
            "browser.sqlite-wal",
            "browser.sqlite-shm",
        ]) {
            const target = path.join(dir, file);
            if (fs.existsSync(target)) fs.unlinkSync(target);
        }
    }
})().catch((error) => {
    console.error(error.message);
    process.exitCode = 1;
});
