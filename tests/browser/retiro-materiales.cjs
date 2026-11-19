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
    const preparation = spawnSync(php, [path.join(__dirname, "preparar-vistas-operativas.php")], {
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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/retiro-materiales-2026-11-16");
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
        const datos=JSON.parse(fs.readFileSync(path.join(dir,'vistas-operativas.json'),'utf8'));
        await login('propietario');
        await page.goto(base+'/admin/pedidos/'+datos.pedido);
        await page.getByRole('button',{name:'Iniciar fabricación',exact:true}).click();
        await page.getByRole('button',{name:'Sí, iniciar',exact:true}).click();await cerrarAviso();
        const pedidoUrl=base+'/admin/pedidos/'+datos.pedido;
        const planUrl=base+'/admin/planificacion/'+datos.pedido;
        await page.goto(planUrl+'?desde=pedido');
        await page.getByRole('link',{name:'Registrar materiales utilizados',exact:true}).click();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',planUrl+'?desde=pedido');
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();await expect(page).toHaveURL(planUrl+'?desde=pedido');
        await page.getByRole('link',{name:'Volver al pedido',exact:true}).click();await expect(page).toHaveURL(pedidoUrl);
        await page.goto(planUrl);
        await page.getByRole('link',{name:'Registrar materiales utilizados',exact:true}).click();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',planUrl);
        await page.locator('[data-uso-cantidad]').first().fill('-1');
        await Promise.all([page.waitForNavigation(),page.locator('[data-materiales-utilizados]').evaluate(form=>HTMLFormElement.prototype.submit.call(form))]);
        await expect(page.locator('.invalid-feedback').first()).toBeVisible();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',planUrl);
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();await expect(page).toHaveURL(planUrl);
        await page.goto(pedidoUrl);await page.getByRole('link',{name:'Registrar materiales utilizados',exact:true}).click();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',pedidoUrl);
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();await expect(page).toHaveURL(pedidoUrl);
        report.comprobaciones.push('Cancelar vuelve a planificación o pedido según origen, incluso tras validación; planificación conserva Volver al pedido');
        await page.goto(base+'/admin/pedidos/'+datos.pedido+'/fabricacion/edit');
        await page.locator('[data-uso-cantidad]').first().fill('6');await page.locator('[data-uso-cantidad]').nth(1).fill('2');
        await page.getByRole('button',{name:'Guardar materiales',exact:true}).click();await cerrarAviso();
        await page.getByRole('link',{name:'Registrar materiales utilizados',exact:true}).click();
        const fila=page.locator('[data-uso-fila]').first();
        await fila.locator('[data-uso-retirar]').click();
        await expect(fila.locator('[data-uso-cantidad]')).toHaveValue('0');
        await expect(page.locator('[data-uso-motivo]')).toHaveAttribute('required','');
        await fila.locator('[data-uso-retirar]').click();await expect(fila.locator('[data-uso-cantidad]')).toHaveValue('6');
        await fila.locator('[data-uso-retirar]').click();await page.locator('[data-uso-motivo]').fill('No se utilizó; corregimos el registro de prueba.');
        for(const width of [1440,390]){await page.setViewportSize({width,height:1000});await noOverflow('Retiro en consumo '+width);await capture('retirar-consumo-'+width);}
        await page.getByRole('button',{name:'Guardar materiales',exact:true}).click();await cerrarAviso();
        await page.getByRole('link',{name:'Editar muebles y precios',exact:true}).click();
        await expect(page.locator('[data-total-cotizacion]')).toHaveText('Bs 190,00');
        const cotizacionUrl=new URL(page.url()).pathname.replace(/\/edit$/,'');
        const segunda=page.locator('[data-material-row]').nth(1);
        await segunda.locator('[data-quitar-material]').click();
        await expect(segunda.locator('[data-cantidad-unitaria]')).toHaveValue('0');
        await expect(segunda.locator('[data-motivo-retiro]')).toHaveAttribute('required','');
        await segunda.locator('[data-quitar-material]').click();await expect(segunda.locator('[data-cantidad-unitaria]')).toHaveValue('2');
        await segunda.locator('[data-quitar-material]').click();await segunda.locator('[data-motivo-retiro]').fill('Material contabilizado por error; no fue utilizado.');
        await page.locator('[data-recargo]').fill('20');
        await expect(page.locator('[data-total-cotizacion]')).toHaveText('Bs 110,00');
        await expect(page.locator('[data-sugerido-cotizacion]')).toHaveText('Bs 132,00');
        for(const width of [1440,390]){await page.setViewportSize({width,height:1000});await noOverflow('Retiro en cotización '+width);await capture('retirar-cotizacion-'+width);}
        await page.locator('[data-theme-toggle]').click();await noOverflow('Retiro oscuro móvil');await capture('retirar-cotizacion-oscuro-390');await page.locator('[data-theme-toggle]').click();
        await page.getByRole('button',{name:'Actualizar cotización',exact:true}).click();await cerrarAviso();
        expect(new URL(page.url()).pathname).toBe(cotizacionUrl);
        await expect(page.getByRole('link',{name:'Volver',exact:true})).toHaveAttribute('href',pedidoUrl);
        await page.getByRole('link',{name:'Editar',exact:true}).click();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',pedidoUrl);
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();await expect(page).toHaveURL(pedidoUrl);
        await page.goto(base+cotizacionUrl+'?desde=pedido');
        const precios=page.locator('form[action*="/aprobar"] input[name^="precios["]');
        await expect(precios.nth(0)).toHaveValue('84');await expect(precios.nth(1)).toHaveValue('48');
        await page.getByRole('button',{name:'Validar y aprobar',exact:true}).click();await cerrarAviso();
        await expect(page.getByRole('link',{name:'Volver',exact:true})).toHaveAttribute('href',pedidoUrl);
        report.comprobaciones.push('Cotización conserva retorno al pedido al editar, guardar, cancelar y aprobar');
        await page.locator('label[for="aceptacion-cotizacion"]').click();await page.getByRole('button',{name:'Actualizar pedido',exact:true}).click();await cerrarAviso();
        await expect(page.locator('[data-pago-precio]')).toContainText('132,00');
        await expect(page.locator('[data-pago-pagado]')).toContainText('75,50');
        await expect(page.locator('[data-pago-saldo]')).toContainText('56,50');
        await page.getByRole('link',{name:'Registrar materiales utilizados',exact:true}).click();
        await expect(page.locator('[data-uso-fila]')).toHaveCount(2);
        for(const campo of await page.locator('[data-uso-cantidad]').all()) await expect(campo).toHaveValue('0');
        report.comprobaciones.push('Quitar y deshacer en ambos formularios; motivo para corregir consumo; costos 190/110 y sugerido 132; aprobación y aceptación; pago 75,50 intacto, saldo 56,50 y dos registros conservados');
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
