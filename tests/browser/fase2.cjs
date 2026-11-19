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
    APP_KEY: 'base64:' + crypto.randomBytes(32).toString('base64'),
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
    const output = path.join(root, "docs/capturas");
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
        await page.goto(`${base}/login`);
        await noOverflow("Login escritorio 1440");
        await capture("login-escritorio");
        await page
            .getByLabel("Correo electrónico", { exact: true })
            .fill("propietario@example.test");
        await page.getByLabel("Contraseña", { exact: true }).fill("incorrecta");
        await page.getByRole("button", { name: "Entrar al panel" }).click();
        await expect(page.getByRole("alert")).toContainText(
            "Estas credenciales no coinciden",
        );
        report.comprobaciones.push(
            "Credenciales incorrectas rechazadas en navegador",
        );
        await login("propietario");
        await expect(page.locator(".metric-card")).toHaveCount(7);
        if (
            !(
                await page
                    .locator(".metric-card")
                    .first()
                    .evaluate((el) => getComputedStyle(el).backgroundImage)
            ).includes("linear-gradient")
        )
            throw new Error("No se aplicó el CSS propio.");
        for (const [label, width, height] of [
            ["escritorio", 1440, 1000],
            ["tableta", 1024, 768],
            ["movil", 390, 844],
        ]) {
            await page.setViewportSize({ width, height });
            await noOverflow(`Panel propietario ${width}`);
            await capture(`panel-propietario-${label}`);
        }
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto(`${base}/admin/usuarios/create`);
        await expect(
            page.getByText("Contraseña inicial", { exact: true }),
        ).toBeVisible();
        await capture("crear-usuario");
        await page
            .getByLabel("Nombre completo", { exact: true })
            .fill("Cuenta de prueba del navegador");
        await page
            .getByLabel("Correo electrónico", { exact: true })
            .fill("nuevo@example.test");
        await page.getByLabel("Tipo de identificación", {exact: false}).selectOption("ci");
        await page.getByLabel("Número de identificación", {exact: false}).fill("90003001");
        await page
            .getByLabel("Rol de acceso", { exact: true })
            .selectOption("maestro");
        await page.getByRole("button", { name: "Crear cuenta" }).click();
        await expect(page).toHaveURL(`${base}/admin/usuarios`);
        await expect(
            page.getByRole("cell", { name: "nuevo@example.test", exact: true }),
        ).toBeVisible();
        report.comprobaciones.push(
            "Alta de maestro desde la interfaz protegida",
        );
        await capture("usuarios-escritorio");
        await page.setViewportSize({ width: 390, height: 844 });
        await noOverflow("Usuarios móvil 390");
        await capture("usuarios-movil");
        await abrirOpcionesCuenta(page);
        await page.getByRole("button", { name: "Salir", exact: true }).click();
        await expect(page).toHaveURL(`${base}/login`);
        await noOverflow("Login móvil 390");
        await capture("login-movil");
        await login("maestro");
        await expect(page.locator("body")).not.toContainText(
            "Resumen administrativo",
        );
        await expect(
            page.getByRole("navigation", { name: "Navegación principal" }),
        ).not.toContainText("Pagos");
        await expect(
            page.getByRole("navigation", { name: "Navegación principal" }),
        ).not.toContainText("Usuarios");
        const unauthorized = await context.request.get(`${base}/admin/usuarios`);
        expect(unauthorized.status()).toBe(403);
        report.comprobaciones.push(
            "Maestro sin acceso a usuarios ni menú financiero",
        );
        await noOverflow("Panel maestro móvil 390");
        await capture("panel-maestro-movil");
        await page.getByRole("button", { name: "Abrir o cerrar menú" }).click();
        await expect(page.locator("body")).toHaveClass(/sidebar-open/);
        await capture("menu-maestro-movil");
        await page
            .getByRole("button", { name: "Cerrar menú", exact: true })
            .click();
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto(`${base}/admin`);
        await capture("panel-maestro-escritorio");
        const fakeLinks = await page.locator(".upcoming a").count();
        expect(fakeLinks).toBe(0);
        report.comprobaciones.push(
            "Módulos próximos sin enlaces; menú móvil abre y cierra",
        );
        expect(errors).toEqual([]);
        report.comprobaciones.push("Sin errores JavaScript");
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


