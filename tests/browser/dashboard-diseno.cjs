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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/dashboard-sin-duplicados-2026-11-17");
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
        async function cerrarAviso(){await expect(page.locator('.swal2-popup')).toBeVisible();await page.evaluate(()=>Swal.close());}
        const datos=JSON.parse(fs.readFileSync(path.join(dir,'datos4a.json'),'utf8'));
        await login('propietario');
        const hoy=new Intl.DateTimeFormat('en-CA',{timeZone:'America/La_Paz'}).format(new Date());
        async function crear(aprobada=false) {
            const token=await page.locator('[name="_token"]').first().inputValue();
            const response=await context.request.post(base+'/admin/cotizaciones/create',{data:{clave_registro:crypto.randomUUID(),fecha:hoy,detalles:[{
                tipo_mueble_id:datos.mesa,material_principal_id:datos.material,largo_cm:'120',ancho_cm:'60',alto_cm:'75',puertas:0,cajones:0,divisiones:0,acabado:'Barnizado natural',cantidad_muebles:1,mano_obra_unitaria:'20',otros_costos_total:'5',
                materiales:[{material_id:datos.material,componente:'General',cantidad_unitaria:'2',costo_unitario_estimado:'40',estado_costo:'conocido',fuente_costo:'referencia_catalogo'}]
            }]},headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},maxRedirects:0});
            expect(response.status(),await response.text()).toBe(302);
            const url=response.headers().location;await page.goto(url);await page.evaluate(()=>window.Swal?.close());
            if(aprobada){const form=page.locator('form[action*="/aprobar"]');await form.locator('input[name^="precios["]').fill('150');await form.getByRole('button',{name:'Validar y aprobar',exact:true}).click();await cerrarAviso();}
            return {url,id:Number(new URL(url).pathname.split('/').pop())};
        }
        for(let i=0;i<3;i++) {
            const cotizacion=await crear(true);
            const token=await page.locator('[name="_token"]').first().inputValue();
            const entrega=new Date();entrega.setDate(entrega.getDate()+i+2);
            const response=await context.request.post(base+'/admin/pedidos/create',{data:{cotizacion_id:cotizacion.id,cliente_id:datos.cliente,aceptacion:true,clave_registro:crypto.randomUUID(),...(i<2?{fecha_prevista_entrega:entrega.toISOString().slice(0,10)}:{})},headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},maxRedirects:0});
            expect(response.status(),await response.text()).toBe(302);
        }
        await page.goto(base+'/admin');await page.evaluate(()=>window.Swal?.close());
        await expect(page.getByRole('heading',{name:'Estado de los pedidos',exact:true})).toBeVisible();
        await expect(page.locator('.workshop-donut-label strong')).toHaveText('3');
        await expect(page.locator('.workshop-chart-legend li')).toHaveCount(4);
        await expect(page.locator('.workshop-chart-legend li').first().locator('strong')).toHaveText('100 %');
        await expect(page.getByRole('link',{name:'Ir a pedidos',exact:true})).toHaveCount(0);
        await expect(page.getByRole('link',{name:'Nueva cotización',exact:true})).toHaveCount(1);
        await expect(page.locator('.workshop-metric')).toHaveCount(4);
        await expect(page.locator('.workshop-order-card')).toHaveCount(3);
        await expect(page.locator('.workshop-board-column')).toHaveCount(4);
        await expect(page.locator('.board-aceptado .workshop-order-card')).toHaveCount(3);
        await expect(page.locator('.board-terminado')).toContainText('No hay pedidos en este estado.');
        await expect(page.locator('.board-entregado')).toContainText('No hay pedidos en este estado.');
        await expect(page.locator('.workshop-order-card[draggable]')).toHaveCount(0);
        await expect(page.locator('.workshop-quotes-table tbody tr')).toHaveCount(3);
        await expect(page.locator('.workshop-welcome')).toBeVisible();
        await expect(page.getByRole('heading',{name:'Todo listo para seguir creando.'})).toBeVisible();
        for(const width of [1440,1024,390]) {
            await page.setViewportSize({width,height:1000});await noOverflow('Dashboard propietario '+width);await capture('dashboard-'+width);
        }
        await page.evaluate(()=>{document.documentElement.dataset.theme='dark';});
        await noOverflow('Dashboard oscuro móvil');await capture('dashboard-oscuro-390');
        await page.setViewportSize({width:1440,height:1000});await capture('dashboard-oscuro-1440');
        await page.evaluate(()=>{document.documentElement.dataset.theme='light';});
        await page.getByRole('link',{name:'Ver pedido 1',exact:true}).click();await expect(page).toHaveURL(/\/admin\/pedidos\/1$/);
        await page.goto(base+'/admin');
        await page.getByRole('link',{name:'Ver cotización 1',exact:true}).click();await expect(page).toHaveURL(/\/admin\/cotizaciones\/1$/);
        await page.goto(base+'/admin');
        await page.getByRole('link',{name:'Nueva cotización',exact:true}).click();await expect(page).toHaveURL(/\/admin\/cotizaciones\/create/);
        await context.clearCookies();await login('maestro');
        await expect(page.locator('.workshop-metric')).toHaveCount(4);
        await expect(page.locator('.workshop-dashboard')).not.toContainText('150,00');
        await page.setViewportSize({width:390,height:1000});await noOverflow('Dashboard maestro móvil');await capture('dashboard-maestro-390');
        report.comprobaciones.push('Indicadores, tablero de cuatro estados con y sin fecha, cotizaciones, accesos, propietario y maestro; sin costos ni datos personales');
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
            "vistas-operativas.json",
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
