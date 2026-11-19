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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/cotizaciones-hora-bolivia-2026-11-17");
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
        const fixture = JSON.parse(fs.readFileSync(path.join(dir,'datos4a.json'),'utf8'));
        const hoy = new Intl.DateTimeFormat('en-CA',{timeZone:'America/La_Paz'}).format(new Date());
        const token = await page.locator('input[name="_token"]').first().inputValue();
        async function cerrarAviso() {
            await expect(page.locator('.swal2-popup')).toBeVisible();
            await page.evaluate(()=>Swal.close());
        }
        async function revisarVista(nombre, ruta) {
            await page.goto(ruta);
            await page.evaluate(()=>window.Swal?.close());
            await expect(page.locator('.page-heading-refined')).toBeVisible();
            if(await page.locator('[data-cotizacion-form]').count()){
                await expect(page.locator('[name="condiciones"]')).toHaveCount(0);
                await expect(page.locator('[name="observaciones"]')).toHaveCount(0);
            }
            for(const width of [1440,1024,390]) {
                await page.setViewportSize({width,height:1000});
                await noOverflow(nombre+' '+width);
                if(width===1440 && await page.locator('.quote-show-layout').count()) {
                    const paneles=await page.locator('.quote-show-overview > section').evaluateAll(els=>els.map(el=>({x:el.getBoundingClientRect().x,y:el.getBoundingClientRect().y,width:el.getBoundingClientRect().width})));
                    expect(paneles).toHaveLength(2);
                    expect(Math.abs(paneles[0].y-paneles[1].y),'Datos y resumen comparten fila').toBeLessThan(2);
                    expect(paneles[1].x).toBeGreaterThan(paneles[0].x+paneles[0].width);
                    const columnas=await page.locator('.quote-show-costs').first().evaluate(el=>getComputedStyle(el).gridTemplateColumns.split(' ').length);
                    expect(columnas,'Los costos breves comparten cuatro columnas').toBe(4);
                    report.comprobaciones.push(nombre+': datos y resumen en una fila, costos en cuatro columnas');
                }
                await capture(nombre+'-'+width);
            }
            await page.locator('[data-theme-toggle]').click();
            await noOverflow(nombre+' oscuro móvil');
            await capture(nombre+'-oscuro-390');
            await page.locator('[data-theme-toggle]').click();
            await page.setViewportSize({width:1440,height:1000});
        }
        await revisarVista('crear',base+'/admin/cotizaciones/create');
        await expect(page.locator('[name="fecha"]')).toHaveCount(0);
        await expect(page.locator('[name="observaciones"]')).toHaveCount(0);
        await expect(page.locator('[name="detalles[0][observaciones]"]')).toHaveCount(1);
        await expect(page.locator('[data-detalle]')).toHaveCount(1);
        await page.locator('[data-agregar-detalle]').click();
        await expect(page.locator('[data-detalle]')).toHaveCount(2);
        await page.locator('[data-quitar-detalle]').last().click();
        await expect(page.locator('[data-detalle]')).toHaveCount(1);
        const ancho=await page.locator('[data-cotizacion-form]').boundingBox();
        const contenedor=await page.locator('.content > .container-fluid').boundingBox();
        expect(ancho.width).toBeLessThan(contenedor.width);
        await verificarCabecera('Registrar cotización');
        report.comprobaciones.push('Alta: ancho contenido, secciones opcionales y añadir/quitar mueble conservados');
        await page.locator('[data-componentes-mueble] > summary').click();
        await page.locator('[data-llenado-manual]').click();
        await page.locator('#d-0-tipo_mueble_id').selectOption(String(fixture.mesa));
        await page.locator('#d-0-material_principal_id').selectOption(String(fixture.material));
        await page.locator('#d-0-acabado').selectOption('Barnizado natural');
        await page.locator('#d-0-largo_cm').fill('120');
        await page.locator('#d-0-ancho_cm').fill('60');
        await page.locator('#d-0-alto_cm').fill('75');
        await page.locator('[data-cantidad-unitaria]').first().fill('2');
        await page.locator('[data-mano-unitaria]').fill('20');
        await page.locator('[data-otros-costos]').fill('5');
        await expect(page.locator('[data-total-cotizacion]')).toContainText(/105[,.]00/);
        const acabado=await page.locator('#d-0-acabado').locator('..').locator('..').boundingBox();
        const componentes=await page.locator('.quote-components-column').boundingBox();
        expect(Math.abs(acabado.y-componentes.y),'Acabado y componentes comparten fila').toBeLessThan(2);
        for(const width of [1440,1024,390]) {
            await page.setViewportSize({width,height:1000});
            await noOverflow('Alta con componentes y costos abiertos '+width);
            await capture('crear-costos-abiertos-'+width);
        }
        report.comprobaciones.push('Alta agrupada: acabado y componentes en una fila, campos abiertos utilizables y cálculo Bs 105 conservado');
        await page.setViewportSize({width:1440,height:1000});
        await page.locator('[data-costos-opcionales] > summary').click();
        await page.locator('[data-mano-aplicada]').fill('18');
        await page.locator('[data-motivo-mano]').fill('Preparación conjunta del trabajo');
        await page.locator('[name$="[precio_propuesto]"]').fill('140');
        await expect(page.locator('[data-total-cotizacion]')).toHaveText('Bs 103,00');
        await page.locator('[name$="[precio_propuesto]"]').fill('150');
        await expect(page.locator('[data-total-cotizacion]')).toHaveText('Bs 103,00');
        await capture('apartado-opcional-restaurado');
        report.comprobaciones.push('Apartado opcional restaurado: ajuste de mano de obra aplicado y precio propuesto independiente del costo');

        await page.setViewportSize({width:1440,height:1000});

        const datos={
            clave_registro:crypto.randomUUID(),fecha:hoy,observaciones:'Mesa para taller en El Alto.',condiciones:'Entrega coordinada con el cliente.',recargo_porcentaje:'25',
            detalles:[{tipo_mueble_id:fixture.mesa,material_principal_id:fixture.material,largo_cm:'120',ancho_cm:'60',alto_cm:'75',puertas:0,cajones:0,divisiones:0,acabado:'Barnizado natural',cantidad_muebles:1,mano_obra_unitaria:'20',precio_propuesto:'140',otros_costos_total:'5',materiales:[{material_id:fixture.material,componente:'General',cantidad_unitaria:'2',costo_unitario_estimado:'40',estado_costo:'conocido',fuente_costo:'referencia_catalogo'}]}]
        };
        const respuesta=await context.request.post(base+'/admin/cotizaciones/create',{data:datos,headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},maxRedirects:0});
        expect(respuesta.status(),await respuesta.text()).toBe(302);
        const cotizacion=respuesta.headers().location;
        const id=new URL(cotizacion).pathname.split('/').pop();
        await revisarVista('ver-preliminar',cotizacion);
        await expect(page.getByText('Última actualización',{exact:true})).toBeVisible();
        const horaGuardada=await page.locator('.record-field').filter({has:page.locator('dt',{hasText:'Última actualización'})}).locator('dd').innerText();
        const horaRespuesta=new Date(respuesta.headers().date);
        const formatoLocal=new Intl.DateTimeFormat('es-BO',{timeZone:'America/La_Paz',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'});
        const opcionesHora=[horaRespuesta,new Date(horaRespuesta.getTime()-60000)].map(d=>formatoLocal.format(d).replace(',',''));
        expect(opcionesHora).toContain(horaGuardada.trim());
        report.comprobaciones.push('Hora de guardado mostrada en America/La_Paz, sin adelanto UTC');
        const primeraFicha=page.locator('.record-fields-boxed').first();
        expect(await primeraFicha.evaluate(el=>getComputedStyle(el).gridTemplateColumns.split(' ').length)).toBe(2);
        await expect(page.getByText('Mesa para taller en El Alto.',{exact:true})).toBeVisible();
        await expect(page.locator('.record-value-icon').first()).toBeVisible();
        await expect(page.locator('form[action*="/aprobar"]')).toBeVisible();
        await revisarVista('editar',base+'/admin/cotizaciones/'+id+'/edit');
        await verificarCabecera('Actualizar cotización');
        for(const [selector,valor]of [['#recargo_porcentaje','25'],['#d-0-largo_cm','120'],['#d-0-ancho_cm','60'],['#d-0-alto_cm','75'],['[data-mano-unitaria]','20'],['[data-otros-costos]','5'],['[data-cantidad-unitaria]','2'],['[data-costo-material]','40']]) {
            await expect(page.locator(selector)).toHaveValue(valor);
        }
        report.comprobaciones.push('Edición: porcentaje, medidas, cantidades y costos sin ceros decimales sobrantes');

        await expect(page.locator('[name$="[precio_propuesto]"]')).toHaveValue(/140(?:\.0+)?/);
        await expect(page.locator('[name="fecha"]')).toHaveCount(0);
        await page.getByRole('button',{name:'Actualizar cotización',exact:true}).click();
        await cerrarAviso();
        await expect(page).toHaveURL(cotizacion);
        await expect(page.getByText('Mesa para taller en El Alto.',{exact:true})).toBeVisible();
        const aprobar=page.locator('form[action*="/aprobar"]');
        await aprobar.locator('input[name^="precios["]').fill('131.25');
        await aprobar.getByRole('button',{name:'Validar y aprobar',exact:true}).click();
        await cerrarAviso();
        await expect(page.getByRole('link',{name:'Editar',exact:true})).toHaveCount(0);
        await expect(page.locator('form[action*="/aprobar"]')).toHaveCount(0);
        await revisarVista('ver-aprobada',cotizacion);
        const pdf=await context.request.get(await page.getByRole('link',{name:'Ver PDF',exact:true}).getAttribute('href'));
        expect(pdf.status()).toBe(200);
        expect(pdf.headers()['content-type']).toContain('application/pdf');
        report.comprobaciones.push('Edición, aprobación, protección posterior y PDF aprobados con importes de prueba');
        await revisarVista('listado',base+'/admin/cotizaciones');
        const espacioAcciones=await page.locator('.table-list-column tbody tr').first().evaluate(fila=>{
            const celda=fila.lastElementChild;
            const ultimo=celda.querySelector('.table-actions').lastElementChild;
            return {sobrante:celda.getBoundingClientRect().right-ultimo.getBoundingClientRect().right,relleno:parseFloat(getComputedStyle(celda).paddingRight)};
        });
        expect(espacioAcciones.sobrante,'Sin espacio extra después del último botón').toBeLessThanOrEqual(espacioAcciones.relleno+2);
        report.comprobaciones.push('Acciones: tabla ajustada al contenido y sin espacio extra después del último botón');
        await page.locator('#q').fill(id);
        await page.getByRole('button',{name:'Buscar',exact:true}).click();
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(1);
        await page.getByRole('link',{name:'Limpiar',exact:true}).click();
        await expect(page.locator('#q')).toHaveValue('');
        report.comprobaciones.push('Listado: búsqueda por ID y limpieza conservadas');
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await login('maestro');
        await page.goto(cotizacion);
        await expect(page.getByText('Costo total aplicado',{exact:true})).toBeVisible();
        await expect(page.getByText('Ganancia sobre el costo',{exact:true})).toHaveCount(0);
        await expect(page.locator('form[action*="/aprobar"]')).toHaveCount(0);
        await expect(page.getByRole('link',{name:'Editar',exact:true})).toHaveCount(0);
        await noOverflow('Consulta del maestro');
        await capture('ver-maestro');
        report.comprobaciones.push('Maestro: costos visibles, sin ganancia ni aprobación y sin edición de cotización aprobada');
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
