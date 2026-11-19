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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/existencias-revision-2026-11-13");
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
        const fixture = JSON.parse(fs.readFileSync(path.join(dir,'datos4a.json'),'utf8'));
        const material = fixture.material;
        const listado = '/admin/existencias?material_id='+material;
        const detalle = '/admin/existencias/'+material;
        async function vistas(url,nombre,formulario=false) {
            await page.goto(base+url);
            for(const width of [1440,1024,390]) {
                await page.setViewportSize({width,height:1000});
                await expect(page.locator('.page-heading-refined')).toBeVisible();
                await noOverflow(nombre+' '+width);
                await capture(nombre+'-'+width);
            }
            await page.locator('[data-theme-toggle]').click();
            await noOverflow(nombre+' oscuro');
            await capture(nombre+'-oscuro-390');
            await page.locator('[data-theme-toggle]').click();
            await page.setViewportSize({width:1440,height:1000});
            if(formulario) {
                await verificarCabecera(nombre);
                const panel=await page.locator('.form-panel').boundingBox();
                const contenido=await page.locator('.content > .container-fluid').boundingBox();
                expect(panel.width).toBeLessThan(contenido.width);
                const campos=await page.locator('#cantidad,#motivo').evaluateAll(els=>els.map(el=>el.getBoundingClientRect().top));
                expect(Math.abs(campos[0]-campos[1])).toBeLessThan(2);
                report.comprobaciones.push(nombre+': formulario contenido y dos campos alineados');
            }
        }
        await vistas(listado,'existencias-listado');
        await expect(page.locator('.content-wrapper tbody a strong').first()).toBeVisible();
        await vistas(detalle+'/create','existencias-agregar',true);
        await page.locator('#cantidad').fill('9');
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/existencias');
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(3);
        await page.goto(base+detalle+'/create');
        await page.locator('#cantidad').fill('5.5');
        await page.locator('#motivo').fill('Material recibido para el taller');
        const datos=await page.locator('form').filter({has:page.locator('#cantidad')}).evaluate(el=>Object.fromEntries(new FormData(el)));
        await page.getByRole('button',{name:'Agregar cantidad',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Cantidad agregada correctamente');
        await page.evaluate(()=>Swal.close());
        await expect(page).toHaveURL(base+listado);
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(1);
        await page.getByRole('link',{name:'Listado general',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/existencias');
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(3);
        const repetida=await context.request.post(base+detalle+'/create',{form:datos,maxRedirects:0});
        expect(repetida.status()).toBe(302);
        await vistas(detalle+'/edit','existencias-corregir',true);
        await expect(page.locator('#cantidad')).toHaveValue('5.5000');
        await page.locator('#cantidad').fill('99');
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/existencias');
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(3);
        await page.goto(base+detalle+'/edit');
        await expect(page.locator('#cantidad')).toHaveValue('5.5000');
        await page.locator('#cantidad').fill('3.25');
        await page.locator('#motivo').fill('Recuento físico del material disponible');
        await page.getByRole('button',{name:'Editar cantidad',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Existencia corregida correctamente');
        await page.evaluate(()=>Swal.close());
        await expect(page).toHaveURL(base+listado);
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(1);
        await page.getByRole('link',{name:'Listado general',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/existencias');
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(3);
        await vistas(detalle,'existencias-movimientos');
        await expect(page.locator('.record-fields')).toContainText('3,25');
        const campos=await page.locator('.record-fields .record-field').evaluateAll(els=>els.map(el=>el.getBoundingClientRect().top));
        expect(Math.abs(campos[0]-campos[1])).toBeLessThan(2);
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(2);
        await expect(page.locator('.content-wrapper tbody')).toContainText('Recuento físico del material disponible');
        await expect(page.locator('.content-wrapper tbody')).toContainText('Disminución');
        await page.getByRole('link',{name:'Volver',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/existencias');
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(3);
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await login('maestro');
        await page.goto(base+listado);
        await expect(page.getByRole('link',{name:'Agregar cantidad',exact:true})).toHaveCount(0);
        await expect(page.getByRole('link',{name:'Editar cantidad',exact:true})).toHaveCount(0);
        await page.getByRole('link',{name:'Ver movimientos',exact:true}).click();
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(2);
        expect((await context.request.get(base+detalle+'/create')).status()).toBe(403);
        expect((await context.request.get(base+detalle+'/edit')).status()).toBe(403);
        report.comprobaciones.push('Agregar 5,5 y corregir a 3,25 conserva dos movimientos; reenviar la entrada no duplica registros');
        report.comprobaciones.push('Ficha en dos columnas y regreso al listado; maestro consulta movimientos sin administrar cantidades');
        report.comprobaciones.push('Después de registrar o editar se muestra un material; Listado general permite recuperar los tres materiales');
        report.comprobaciones.push('Cancelar en agregar/editar vuelve al listado general sin guardar; guardar conserva el resultado individual');
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
