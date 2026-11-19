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
    const output = process.env.TAJIBO_CAPTURAS || path.join(root, "docs/capturas/permisos-usuarios-bloqueados-2026-11-14/individual");
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
        async function cerrarAviso() {
            await expect(page.locator('.swal2-popup')).toContainText('Permisos individuales actualizados');
            await page.evaluate(()=>Swal.close());
        }

        await login('propietario');
        await page.goto(base+'/admin/usuarios');
        const fila = page.locator('.content-wrapper tbody tr').filter({hasText:'maestro@example.test'});
        const url = await fila.getByRole('link',{name:'Permisos',exact:true}).getAttribute('href');
        await expect(page.locator('.content-wrapper tbody tr').filter({hasText:'propietario@example.test'}).getByRole('link',{name:'Permisos',exact:true})).toHaveCount(1);
        await page.goto(url);
        await expect(page.locator('.permissions-review input[type="checkbox"][data-permiso]')).toHaveCount(48);
        await expect(page.locator('input[data-permiso^="usuarios."]:disabled')).toHaveCount(5);
        await expect(page.locator('input[data-permiso^="usuarios."]:checked')).toHaveCount(0);
        for (const width of [1440,1024,390]) {
            await page.setViewportSize({width,height:1000});
            await noOverflow('Permisos individuales '+width);
            await capture('individuales-'+width);
        }
        await page.locator('[data-theme-toggle]').click();
        await noOverflow('Permisos individuales oscuro móvil');
        await capture('individuales-oscuro-390');
        await page.locator('[data-theme-toggle]').click();
        await page.setViewportSize({width:1440,height:1000});
        const selector = permiso => page.locator('input[type="checkbox"][data-permiso="'+permiso+'"]');
        async function marcar(permiso, marcado) {
            const casilla = selector(permiso);
            if (await casilla.isChecked() !== marcado) {
                const id = await casilla.getAttribute('id');
                await page.locator('label[for="'+id+'"]').click();
            }
        }
        for (const permiso of ['clientes.crear','clientes.modificar','pagos.consultar','pagos.gestionar','cotizaciones.aprobar']) await marcar(permiso, true);
        await marcar('materiales.consultar', false);
        await page.getByRole('button',{name:'Guardar permisos individuales',exact:true}).click();
        await cerrarAviso();
        await expect(selector('pagos.gestionar')).toBeChecked();
        await expect(selector('materiales.consultar')).not.toBeChecked();
        const token = await page.locator('.permissions-review input[name="_token"]').inputValue();
        const ataque = await context.request.put(url,{data:{_token:token,reglas:{999999:'permitir'}},headers:{Accept:'application/json'}});
        expect(ataque.status()).toBe(422);
        await capture('individuales-guardados');
        report.comprobaciones.push('Propietario concede pagos y aprobación y deniega consulta heredada; servidor rechaza ID inexistente');
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await login('maestro');
        await page.goto(base+'/admin/clientes/create');
        await page.locator('#nombre').fill('Cliente de delegación');
        await page.locator('#telefono').fill('71234567');
        await page.locator('form[action$="/admin/clientes/create"] button[type="submit"]').click();
        await expect(page.locator('.swal2-popup')).toContainText('Cliente registrado');
        await page.evaluate(()=>Swal.close());
        await page.locator('.content-wrapper tbody tr').filter({hasText:'Cliente de delegación'}).getByRole('link',{name:/Editar/}).click();
        await expect(page.locator('#nombre')).toHaveValue('Cliente de delegación');
        await capture('maestro-edita-cliente');
        expect((await context.request.get(base+'/admin/materiales')).status()).toBe(403);
        expect((await context.request.get(base+'/admin/pagos')).status()).toBe(200);
        expect((await context.request.get(base+'/admin/pagos/create')).status()).toBe(200);
        expect((await context.request.get(base+'/admin/usuarios')).status()).toBe(403);
        expect((await context.request.get(url)).status()).toBe(403);
        report.comprobaciones.push('Maestro autorizado consulta/registra pagos y clientes; consulta de materiales denegada, sin autogestión de permisos');
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await login('propietario');
        await page.goto(url);
        await page.getByRole('button', {name:'Usar permisos del rol', exact:true}).click();
        await page.getByRole('button',{name:'Guardar permisos individuales',exact:true}).click();
        await cerrarAviso();
        await expect(selector('materiales.consultar')).toBeChecked();
        await expect(page.locator('input[data-permiso^="usuarios."]:disabled')).toHaveCount(5);
        await expect(page.locator('input[data-permiso^="usuarios."]:checked')).toHaveCount(0);
        await expect(page.locator('[data-regla-permiso][value="heredar"]')).toHaveCount(48);
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await login('maestro');
        expect((await context.request.get(base+'/admin/clientes/create')).status()).toBe(403);
        expect((await context.request.get(base+'/admin/pagos')).status()).toBe(403);
        expect((await context.request.get(base+'/admin/materiales')).status()).toBe(200);
        await page.goto(base+'/admin/clientes');
        await expect(page.locator('.content-wrapper tbody')).toContainText('Cliente de delegación');
        report.comprobaciones.push('Según su rol retira concesiones y denegaciones individuales, conservando registros y recuperando la consulta heredada');
        report.comprobaciones.push('Usuarios permanece bloqueado y desmarcado al guardar y restablecer el rol del maestro');
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
