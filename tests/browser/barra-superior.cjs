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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/barra-perfil-2026-11-11");
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
        async function abrirCuenta() { await abrirOpcionesCuenta(page); }
        async function verificarBarra(etiqueta){
            await expect(page.getByRole('button',{name:'Mostrar calendario',exact:true})).toHaveCount(0);
            await expect(page.locator('[data-calendar]')).toHaveCount(0);
            const limites=await page.locator('.main-header .btn, .main-header [data-widget="pushmenu"]').evaluateAll(els=>els.filter(el=>el.getClientRects().length).map(el=>{const r=el.getBoundingClientRect();return {left:r.left,right:r.right,top:r.top,bottom:r.bottom}}));
            const ancho=await page.evaluate(()=>innerWidth);
            for(const r of limites){expect(r.left).toBeGreaterThanOrEqual(0);expect(r.right).toBeLessThanOrEqual(ancho+1);}
            for(let i=1;i<limites.length;i++)expect(limites[i].left).toBeGreaterThanOrEqual(limites[i-1].right-1);
            const identidad=await page.locator('.workspace-label').boundingBox();
            if(identidad && identidad.width>0){const tema=await page.locator('[data-theme-toggle]').boundingBox();expect(identidad.x+identidad.width).toBeLessThanOrEqual(tema.x);}
            await expect(page.locator('.main-sidebar .sidebar-account')).toHaveCount(0);
            await expect(page.locator('#header-account-menu')).toBeHidden();
            await abrirCuenta();
            await expect(page.getByRole('link',{name:'Mi perfil',exact:true})).toBeInViewport();
            await expect(page.getByRole('button',{name:'Salir',exact:true})).toBeInViewport();
            const cuenta = await page.locator('#header-account-toggle').boundingBox();
            const tema = await page.locator('[data-theme-toggle]').boundingBox();
            expect(cuenta.x).toBeGreaterThanOrEqual(tema.x + tema.width - 1);
            expect(cuenta.x - tema.x - tema.width).toBeLessThan(20);
            const desplegable = await page.locator('#header-account-menu').boundingBox();
            expect(desplegable.x).toBeGreaterThanOrEqual(0);
            expect(desplegable.x + desplegable.width).toBeLessThanOrEqual(ancho);
            const ultimaOpcion = page.locator('.main-sidebar nav a.nav-link').last();
            if (ancho >= 992) {
                await ultimaOpcion.scrollIntoViewIfNeeded();
                await expect(ultimaOpcion).toBeInViewport();
                await page.locator('.main-sidebar nav a.nav-link').first().scrollIntoViewIfNeeded();
            }
            if (etiqueta.endsWith('claro 1440') || etiqueta.endsWith('claro 320')) {
                const captura = etiqueta.replaceAll(' ','-')+'-cuenta.png';
                await page.screenshot({path:path.join(output,captura)});
                report.capturas.push(captura);
            }
            await page.keyboard.press('Escape');
            await expect(page.locator('#header-account-menu')).toBeHidden();
            await expect(page.locator('#header-account-toggle')).toHaveAttribute('aria-expanded','false');
            await expect(page.locator('#header-account-toggle')).toBeFocused();
            await abrirCuenta();
            await page.mouse.click(2,110);
            await expect(page.locator('#header-account-menu')).toBeHidden();
            report.comprobaciones.push(etiqueta+': acciones visibles, sin recorte ni superposición');
        }
        async function capturarBarra(nombre){await page.locator('.main-header').screenshot({path:path.join(output,nombre+'.png')});report.capturas.push(nombre+'.png');}
        for(const rol of ['propietario','maestro']){
            await login(rol);
            await expect(page.locator('#header-account-role')).toHaveText(rol==='propietario'?'Propietario':'Maestro carpintero');
            for(const width of [1440,1200,1024,768,576,390,320]){
                await page.setViewportSize({width,height:1000});
                await verificarBarra(rol+' claro '+width);await capturarBarra('barra-'+rol+'-'+width);
                await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();
                await expect(page.locator('html')).toHaveAttribute('data-theme','dark');await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed','true');
                await page.reload();await expect(page.locator('html')).toHaveAttribute('data-theme','dark');
                await verificarBarra(rol+' oscuro '+width);await capturarBarra('barra-'+rol+'-oscuro-'+width);
                await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();await expect(page.locator('html')).toHaveAttribute('data-theme','light');
            }
            await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).focus();await page.keyboard.press('Enter');await expect(page.locator('html')).toHaveAttribute('data-theme','dark');
            await page.keyboard.press('Enter');await expect(page.locator('html')).toHaveAttribute('data-theme','light');
            await abrirCuenta();
            await abrirOpcionesCuenta(page);
            await page.getByRole('link',{name:'Mi perfil',exact:true}).focus();await page.keyboard.press('Enter');
            await expect(page).toHaveURL(base+'/admin/perfil');await expect(page.getByRole('heading',{name:'Mi perfil',exact:true})).toBeVisible();
            await abrirCuenta();
            await abrirOpcionesCuenta(page);
            await page.getByRole('button',{name:'Salir',exact:true}).focus();await page.keyboard.press('Enter');
            await expect(page).toHaveURL(base+'/login');await page.goto(base+'/admin');await expect(page).toHaveURL(base+'/login');
            report.comprobaciones.push(rol+': rol correcto, tema persistente, teclado, acceso a perfil y cierre de sesión efectivo');
        }
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
