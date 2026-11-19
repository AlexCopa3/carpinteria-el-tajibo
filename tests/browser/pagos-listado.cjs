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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/pagos-listado-2026-11-16");
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
        const listado=base+'/admin/pagos';
        const ficha=base+'/admin/pagos/pedido/'+datos.pedido;
        await page.goto(listado);
        await expect(page.getByRole('table')).toContainText('600,00');
        await expect(page.getByRole('table')).toContainText('75,50');
        await expect(page.getByRole('table')).toContainText('524,50');
        await page.locator('#q').fill('Cliente que no existe QA');await page.getByRole('button',{name:'Buscar',exact:true}).click();
        await expect(page.getByRole('table')).toContainText('No hay pedidos para estos filtros.');
        await page.getByRole('link',{name:'Limpiar',exact:true}).click();await expect(page).toHaveURL(listado);
        for(const width of [1440,390]){await page.setViewportSize({width,height:1000});await noOverflow('Listado de cobros '+width);await capture('pagos-pedidos-'+width);}
        await page.getByRole('link',{name:'Ver',exact:true}).first().click();await expect(page).toHaveURL(ficha);
        await expect(page.getByRole('region',{name:'Historial de pagos del pedido '+datos.pedido})).toContainText('75,50');
        await page.getByRole('link',{name:'Registrar pago',exact:true}).click();
        await expect(page.locator('#pedido_id')).toHaveValue(String(datos.pedido));
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',ficha);
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();await expect(page).toHaveURL(ficha);
        await page.getByRole('link',{name:'Registrar pago',exact:true}).click();
        await page.locator('#importe').fill('100');await page.locator('#medio').selectOption('qr');
        await page.getByRole('button',{name:'Registrar pago',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();await cerrarAviso();
        await expect(page).toHaveURL(ficha);
        await expect(page.locator('[data-pago-pagado]')).toHaveText('Bs 175,50');await expect(page.locator('[data-pago-saldo]')).toHaveText('Bs 424,50');
        const historial=page.getByRole('region',{name:'Historial de pagos del pedido '+datos.pedido});
        await expect(historial.getByRole('row')).toHaveCount(3);
        await historial.getByRole('link',{name:'Editar',exact:true}).first().click();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',ficha);
        await page.locator('#importe').fill('125');await page.locator('#motivo').fill('Importe recibido corregido en prueba aislada.');
        await page.getByRole('button',{name:'Actualizar pago',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();await cerrarAviso();
        await expect(page).toHaveURL(ficha);await expect(page.locator('[data-pago-pagado]')).toHaveText('Bs 200,50');await expect(page.locator('[data-pago-saldo]')).toHaveText('Bs 399,50');
        await historial.getByRole('link',{name:'Ver',exact:true}).first().click();
        await expect(page.getByRole('link',{name:'Volver a pagos',exact:true})).toHaveAttribute('href',ficha);
        await page.getByRole('link',{name:'Volver a pagos',exact:true}).click();await expect(page).toHaveURL(ficha);
        for(const width of [1440,1024,390]){await page.setViewportSize({width,height:1000});await noOverflow('Pagos del pedido '+width);await capture('pagos-pedido-'+width);}
        await page.locator('[data-theme-toggle]').click();await noOverflow('Pagos oscuro móvil');await capture('pagos-pedido-oscuro-390');await page.locator('[data-theme-toggle]').click();
        await page.getByRole('link',{name:'Volver al listado',exact:true}).click();await expect(page).toHaveURL(listado);
        await expect(page.getByRole('table')).toContainText('399,50');
        await page.goto(base+'/admin/pedidos/'+datos.pedido);await page.getByRole('link',{name:'Ver pagos',exact:true}).click();
        await expect(page).toHaveURL(ficha+'?desde=pedido');
        await page.getByRole('link',{name:'Registrar pago',exact:true}).click();
        await expect(page.getByRole('link',{name:'Cancelar',exact:true})).toHaveAttribute('href',ficha+'?desde=pedido');
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();await page.getByRole('link',{name:'Volver al pedido',exact:true}).click();await expect(page).toHaveURL(base+'/admin/pedidos/'+datos.pedido);
        report.comprobaciones.push('Pedidos con cliente, total, pagado y saldo; Ver abre historial; registrar QR y editar regresan a la ficha sin duplicar pagos; saldos 424,50/399,50; búsqueda, limpieza y retornos contextuales correctos');
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
