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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/tablas-contenidas-2026-11-13");
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
            const fondoAccion = await page.locator('.form-themed button[type="submit"]').first().evaluate(el=>getComputedStyle(el).backgroundImage);
            expect(fondoCabecera).toBe(fondoAccion);
            report.comprobaciones.push(label+': cabecera con el mismo degradado que su acción');
        }
        await login('propietario');
        const fixture=JSON.parse(fs.readFileSync(path.join(dir,'datos4a.json'),'utf8'));
        const token=await page.locator('input[name="_token"]').first().inputValue();
        const hoy=new Intl.DateTimeFormat('en-CA',{timeZone:'America/La_Paz'}).format(new Date());
        async function registrar(url,data) {
            const response=await context.request.post(base+url,{data,headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},maxRedirects:0});
            if(response.status()!==302)throw Error('No se pudo preparar fixture '+url+': '+await response.text());
            return response.headers().location;
        }
        const cotizacion=await registrar('/admin/cotizaciones/create',{
            clave_registro:crypto.randomUUID(),fecha:hoy,
            detalles:[{tipo_mueble_id:fixture.mesa,material_principal_id:fixture.material,largo_cm:'120',ancho_cm:'60',alto_cm:'75',puertas:0,cajones:0,divisiones:0,acabado:'Barnizado natural',cantidad_muebles:1,mano_obra_unitaria:'20',otros_costos_total:'5',materiales:[{material_id:fixture.material,componente:'General',cantidad_unitaria:'2',costo_unitario_estimado:'40',estado_costo:'conocido',fuente_costo:'referencia_catalogo'}]}]
        });
        await page.goto(cotizacion);
        const aprobar=page.locator('form[action*="/aprobar"]');
        await aprobar.locator('input[name^="precios["]').fill('200');
        await aprobar.getByRole('button',{name:'Validar y aprobar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toBeVisible();await page.evaluate(()=>Swal.close());
        const cotizacionId=Number(new URL(cotizacion).pathname.split('/').pop());
        const pedido=await registrar('/admin/pedidos/create',{cotizacion_id:cotizacionId,clave_registro:crypto.randomUUID(),aceptacion:true,cliente_id:fixture.cliente});
        const pedidoId=Number(new URL(pedido).pathname.split('/').pop());
        await registrar('/admin/pagos/create',{pedido_id:pedidoId,clave_registro:crypto.randomUUID(),fecha:hoy,importe:'50.00',medio:'efectivo'});
        await registrar('/admin/clientes/create',{nombre:'Adriana Vargas Lima',tipo:'persona',telefono:'+591 71020013',email:'contacto.13@example.test',activo:true});
        const modulos=['clientes','categorias','usuarios','cotizaciones','pedidos','pagos','planificacion','materiales','existencias'];
        for(const modulo of modulos) {
            const anchoContenido = ['clientes', 'existencias', 'cotizaciones'].includes(modulo);
            await page.goto(base+'/admin/'+modulo);
            await page.evaluate(()=>{if(window.Swal)window.Swal.close();});
            await expect(page.locator('.swal2-container')).toHaveCount(0);
            await expect(page.locator('.content-wrapper tbody tr').first()).toBeVisible();
            for(const width of [1920,1440,1280,1024,390]) {
                await page.setViewportSize({width,height:1000});
                await noOverflow(modulo+' '+width);
                const columna=await page.locator('.table-list-column').boundingBox();
                const contenedor=await page.locator('.content > .container-fluid').boundingBox();
                if(width>=1200) {
                    if(anchoContenido)expect(columna.width).toBeLessThanOrEqual(contenedor.width+1);
                    else expect(columna.width).toBeLessThan(contenedor.width);
                }
                if(width>=1200 && !anchoContenido && await page.locator('.content-header .page-actions').count()) {
                    const accionesSuperiores=await page.locator('.content-header .page-actions').boundingBox();
                    expect(accionesSuperiores.x+accionesSuperiores.width,'Acciones superiores de '+modulo+' a la derecha de la tabla').toBeGreaterThan(columna.x+columna.width+30);
                }
                if(width>=1440) {
                    const medidas=await page.locator('.table-list-column .table-responsive').first().evaluate(el=>({tabla:el.scrollWidth,visible:el.clientWidth}));
                    expect(medidas.tabla,'Tabla '+modulo+' a '+width).toBeLessThanOrEqual(medidas.visible+1);
                    if(anchoContenido) {
                        const espacio=await page.locator('.table-list-column tbody tr').first().evaluate(fila=>{
                            const celda=fila.lastElementChild;
                            const acciones=celda.querySelector('.table-actions');
                            return {sobrante:celda.getBoundingClientRect().right-acciones.getBoundingClientRect().right,relleno:parseFloat(getComputedStyle(celda).paddingRight)};
                        });
                        expect(espacio.sobrante,'Sin espacio extra después de los botones').toBeLessThanOrEqual(espacio.relleno+2);
                        const bordes=await page.locator('.table-list-column .table-responsive').evaluate(el=>({tarjeta:el.getBoundingClientRect().right,tabla:el.querySelector('table').getBoundingClientRect().right}));
                        expect(Math.abs(bordes.tarjeta-bordes.tabla),'La tarjeta se ajusta a la tabla').toBeLessThan(3);
                    }
                    for(const acciones of await page.locator('.table-list-column tbody tr .table-actions').all()) {
                        const alturas=await acciones.locator(':scope > *').evaluateAll(els=>els.map(el=>el.getBoundingClientRect().top));
                        if(alturas.length>1)expect(Math.max(...alturas)-Math.min(...alturas)).toBeLessThan(2);
                    }
                }
                await capture(modulo+'-'+width);
            }
            await page.locator('[data-theme-toggle]').click();
            await noOverflow(modulo+' oscuro móvil');await capture(modulo+'-oscuro-390');
            await page.locator('[data-theme-toggle]').click();
            report.comprobaciones.push(modulo+': panel contenido en escritorio, tabla y acciones sin recortes a 1440/1920 px y ancho móvil disponible');
        }
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
