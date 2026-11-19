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
    const output = process.env.TAJIBO_CAPTURAS || path.join(root, "docs/capturas/permisos-usuarios-bloqueados-2026-11-14/general");
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
        async function revisarVista(nombre) {
            for (const width of [1440, 1024, 390]) {
                await page.setViewportSize({width,height:1000});
                await noOverflow('Permisos '+nombre+' '+width);
                await expect(page.locator('.page-heading-refined')).toBeVisible();
                if (nombre === 'listado' && width === 1440) {
                    const tarjeta = await page.locator('.permissions-index-column').boundingBox();
                    const contenedor = await page.locator('.content > .container-fluid').boundingBox();
                    expect(tarjeta.width).toBeLessThan(contenedor.width);
                }
                await capture(nombre+'-'+width);
            }
            await page.locator('[data-theme-toggle]').click();
            await expect(page.locator('html')).toHaveAttribute('data-theme','dark');
            await noOverflow('Permisos '+nombre+' oscuro móvil');
            await capture(nombre+'-oscuro-390');
            await page.locator('[data-theme-toggle]').click();
            await page.setViewportSize({width:1440,height:1000});
        }
        await login('propietario');
        await page.goto(base+'/admin/usuarios');
        await page.getByRole('link',{name:'Ver permisos',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/permisos');
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(2);
        const maestroUrl = await page.locator('.content-wrapper tbody tr').filter({hasText:'Maestro carpintero'}).getByRole('link',{name:'Revisar permisos',exact:true}).getAttribute('href');
        const propietarioUrl = await page.locator('.content-wrapper tbody tr').filter({hasText:'Propietario'}).getByRole('link',{name:'Revisar permisos',exact:true}).getAttribute('href');
        await revisarVista('listado');
        await page.goto(maestroUrl);
        await expect(page.locator('#permiso-costos-consultar')).toBeChecked();
        await expect(page.locator('#permiso-costos-consultar')).toBeEnabled();
        await expect(page.locator('input[type="hidden"][value="costos.consultar"]')).toHaveCount(0);
        await expect(page.locator('#permiso-cotizaciones-aprobar')).not.toBeChecked();
        await expect(page.locator('#permiso-cotizaciones-aprobar')).toBeEnabled();
        await expect(page.locator('#permiso-casos-consultar')).toBeEnabled();
        await expect(page.locator('input[type="checkbox"][id^="permiso-usuarios-"]')).toHaveCount(5);
        await expect(page.locator('input[type="checkbox"][id^="permiso-usuarios-"]:disabled')).toHaveCount(5);
        await expect(page.locator('input[type="checkbox"][id^="permiso-usuarios-"]:checked')).toHaveCount(0);
        report.comprobaciones.push('Usuarios bloqueado y desmarcado para maestro; otros módulos editables');
        await revisarVista('maestro');
        await page.locator('label[for="permiso-casos-consultar"]').click();
        await expect(page.locator('#permiso-casos-consultar')).not.toBeChecked();
        await page.getByRole('button',{name:'Guardar permisos',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Permisos del rol actualizados');
        await page.evaluate(()=>Swal.close());
        await expect(page.locator('#permiso-casos-consultar')).not.toBeChecked();
        await expect(page.locator('#permiso-costos-consultar')).toBeChecked();
        await page.locator('label[for="permiso-casos-consultar"]').click();
        await page.getByRole('button',{name:'Guardar permisos',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Permisos del rol actualizados');
        await page.evaluate(()=>Swal.close());
        await expect(page.locator('#permiso-casos-consultar')).toBeChecked();
        report.comprobaciones.push('Guardar y restaurar un permiso conserva los demás');
        await page.goto(propietarioUrl);
        await expect(page.locator('#permiso-usuarios-gestionar')).toBeChecked();
        await expect(page.locator('input[type="checkbox"][id^="permiso-usuarios-"]:disabled:checked')).toHaveCount(5);
        await revisarVista('propietario');
        await page.getByRole('link',{name:'Volver',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/permisos');
        await page.getByRole('link',{name:'Volver a usuarios',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/usuarios');
        report.comprobaciones.push('Regreso al listado de permisos y a Usuarios funciona sin alterar las rutas');
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await login('maestro');
        expect((await context.request.get(base+'/admin/permisos')).status()).toBe(403);
        expect((await context.request.get(maestroUrl)).status()).toBe(403);
        report.comprobaciones.push('Maestro bloqueado en listado y edición por URL directa');
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
