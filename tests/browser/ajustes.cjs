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
    const preparation = spawnSync(php, [path.join(__dirname, "preparar.php")], {
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
    const output = process.env.TAJIBO_CAPTURAS || path.join(root, "docs/capturas/ajustes");
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
        await page.getByRole('link',{name:'Configuración',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/ajustes');
        await expect(page.getByRole('button',{name:'Guardar ajustes',exact:true})).toBeVisible();
        await expect(page.getByLabel('Moneda', {exact: false})).toHaveCount(0);
        await noOverflow('Configuración escritorio');
        await capture('configuracion-inicial');
        await page.locator('#nombre').fill('El Tajibo Prueba');
        await page.locator('#direccion').fill('Dirección de prueba');
        await page.locator('#telefono').fill('70000000');
        await page.locator('#email').fill('ajustes@example.test');
        await page.getByRole('button',{name:'Guardar ajustes',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/ajustes');
        await expect(page.locator('.swal2-popup')).toContainText('Ajustes guardados correctamente.');
        await page.evaluate(()=>Swal.close());
        await expect(page.getByRole('button',{name:'Actualizar ajustes',exact:true})).toBeVisible();
        await expect(page.locator('.tajibo-brand')).toContainText('El Tajibo Prueba');
        await expect(page.locator('#telefono')).toHaveValue('70000000');
        report.comprobaciones.push('Propietario guarda datos; Configuración actualiza la misma ficha y el nombre del menú');
        await page.locator('#telefono').fill('71111111');
        await page.getByRole('button',{name:'Actualizar ajustes',exact:true}).click();
        await expect(page.locator('#telefono')).toHaveValue('71111111');
        await page.waitForFunction(()=>typeof Swal!=='undefined');
        await page.evaluate(()=>Swal.close());
        await verificarCabecera('Actualizar ajustes');
        for(const width of [1440,1024,390]){
            await page.setViewportSize({width,height:1000});
            await noOverflow('Configuración '+width);
            await expect(page.locator('.ajustes-heading-compact i')).toHaveCount(0);
            expect(await page.locator('.ajustes-heading-compact').evaluate(el => getComputedStyle(el).backgroundImage)).toBe('none');

            const datos = await page.locator('.ajustes-data-column').boundingBox();
            const logo = await page.locator('.ajustes-logo-column').boundingBox();
            if (width >= 992) {
                expect(Math.abs(datos.y - logo.y)).toBeLessThan(2);
                expect(logo.x).toBeGreaterThanOrEqual(datos.x + datos.width - 2);
            } else {
                expect(logo.y).toBeGreaterThanOrEqual(datos.y + datos.height - 2);
            }
            expect(await page.locator('#logo').evaluate(input => input.form === document.querySelector('#nombre').form)).toBe(true);
            report.comprobaciones.push('Configuración '+width+': datos y logo en columnas adaptadas dentro del mismo formulario');
            await capture('configuracion-'+width);
        }
        await expect(page.locator('.ajustes-heading-compact')).toBeVisible();
        await page.locator('[data-theme-toggle]').click();
        for (const width of [1440,390]) {
            await page.setViewportSize({width,height:1000});
            await noOverflow('Encabezado Configuración oscuro '+width);
            await expect(page.locator('.ajustes-heading-compact i')).toHaveCount(0);
            expect(await page.locator('.ajustes-heading-compact').evaluate(el => getComputedStyle(el).backgroundImage)).toBe('none');
            if (width === 1440) {
                const encabezado = await page.locator('.ajustes-heading-compact').boundingBox();
                const contenedor = await page.locator('.content-header .container-fluid').boundingBox();
                expect(encabezado.width).toBeLessThan(contenedor.width);
            }
            await capture('configuracion-oscuro-'+width);
        }
        await page.locator('[data-theme-toggle]').click();
        await page.setViewportSize({width:1440,height:1000});
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await login('maestro');
        await expect(page.getByRole('link',{name:'Configuración',exact:true})).toHaveCount(0);
        expect((await context.request.get(base+'/admin/ajustes')).status()).toBe(403);
        report.comprobaciones.push('Maestro sin acceso a Configuración por menú ni URL directa');
        if(errors.length)throw Error(errors.join('; '));
        report.comprobaciones.push('Sin errores JavaScript');
        report.resultado = "APROBADO";
        fs.writeFileSync(
            path.join(output, "resultado-browser.json"),
            JSON.stringify(report, null, 2),
        );
        console.log(JSON.stringify(report, null, 2));
    } finally {
        if (browser) await browser.close();
        server.kill();
        await new Promise((resolve) => server.once("exit", resolve));
        // Solo archivos temporales creados por esta ejecución; nunca bases/configuración reales.
        for (const file of [
            "credenciales.json",
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
