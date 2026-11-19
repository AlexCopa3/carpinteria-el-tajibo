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
    const prepararCasos=spawnSync(php,[path.join(__dirname,'preparar-casos.php')],{cwd:root,env,encoding:'utf8',windowsHide:true});
    if(prepararCasos.status!==0)throw Error(prepararCasos.stderr||prepararCasos.stdout);
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
        async function cerrarAviso(){await expect(page.locator('.swal2-popup')).toBeVisible();await page.evaluate(()=>Swal.close());}
        const datos=JSON.parse(fs.readFileSync(path.join(dir,'vistas-operativas.json'),'utf8'));
        const casos=JSON.parse(fs.readFileSync(path.join(dir,'casos5a.json'),'utf8'));
        await login('propietario');
        const paginas=[['cliente-ver','/admin/clientes/'+datos.cliente],['pagos','/admin/pagos'],['pago-crear','/admin/pagos/create?pedido_id='+datos.pedido],['pago-editar','/admin/pagos/'+datos.pago+'/edit'],['pago-ver','/admin/pagos/'+datos.pago],['casos','/admin/casos'],['caso-ver','/admin/casos/'+casos.primero],['planificacion','/admin/planificacion'],['planificacion-ver','/admin/planificacion/'+datos.pedido],['planificacion-editar','/admin/planificacion/'+datos.pedido+'/edit']];
        for(const [nombre,url] of paginas){
            await page.goto(base+url);
            await expect(page.locator('.page-heading-refined h1')).toBeVisible();
            for(const width of [1440,1024,390]){
                await page.setViewportSize({width,height:1000});await noOverflow(nombre+' '+width);await capture(nombre+'-'+width);
                const cortados=await page.locator('.module-actions .btn').evaluateAll(botones=>botones.filter(b=>{const r=b.getBoundingClientRect(),p=b.closest('.card-body')?.getBoundingClientRect();return p&&(r.right>p.right+1||r.left<p.left-1);}).length);
                expect(cortados).toBe(0);
            }
            await page.locator('[data-theme-toggle]').click();await noOverflow(nombre+' oscuro');await capture(nombre+'-oscuro-390');await page.locator('[data-theme-toggle]').click();
        }
        await page.setViewportSize({width:1440,height:1000});
        await page.goto(base+'/admin/clientes/'+datos.cliente);
        await page.locator('#pedidos-cliente').getByRole('link',{name:'Ver',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/pedidos/'+datos.pedido);
        report.comprobaciones.push('Cliente: listado de sus pedidos y enlace a su detalle');
        await page.goto(base+'/admin/planificacion/'+datos.pedido+'/edit');
        const antes=await page.locator('[data-plan-fila]').count();await page.locator('[data-plan-agregar]').first().click();await expect(page.locator('[data-plan-fila]')).toHaveCount(antes+1);await page.locator('[data-plan-quitar]').click();await expect(page.locator('[data-plan-fila]')).toHaveCount(antes);
        await page.locator('[name$="[cantidad_planificada]"]').first().fill('5');await page.locator('#motivo').fill('Cantidad revisada en prueba aislada');await page.getByRole('button',{name:'Actualizar materiales',exact:true}).click();await cerrarAviso();await expect(page).toHaveURL(new RegExp('/admin/planificacion/'+datos.pedido+'$'));
        report.comprobaciones.push('Planificación: añadir/quitar y guardar cantidad con retorno conservado');
        await page.goto(base+'/admin/pagos/'+datos.pago+'/edit');await page.locator('#importe').fill('100');await page.locator('#medio').selectOption('qr');await page.locator('#motivo').fill('Corrección de importe en prueba aislada');await page.getByRole('button',{name:'Actualizar pago',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();await cerrarAviso();await expect(page).toHaveURL(base+'/admin/pagos/'+datos.pago);await expect(page.locator('[data-pago-pagado]')).toHaveText('Bs 100,00');await expect(page.locator('[data-pago-saldo]')).toHaveText('Bs 500,00');
        report.comprobaciones.push('Pago editado con QR, confirmación, importe 100 y saldo 500');
        await abrirOpcionesCuenta(page);await page.getByRole('button',{name:'Salir',exact:true}).click();await login('maestro');expect((await context.request.get(base+'/admin/pagos')).status()).toBe(403);
        await page.goto(base+'/admin/casos/'+casos.primero);await expect(page.getByRole('heading',{name:'Materiales del caso',exact:true})).toBeVisible();await page.goto(base+'/admin/planificacion/'+datos.pedido);await expect(page.getByRole('heading',{name:'Materiales necesarios',exact:true})).toBeVisible();report.comprobaciones.push('Maestro consulta casos y planificación; Pagos sigue protegido');
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
