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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/tipos-mueble-2026-11-14");
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
            const fondoAccion = await page.locator('[data-cotizacion-form] button[type="submit"]').first().evaluate(el=>getComputedStyle(el).backgroundImage);
            expect(fondoCabecera).toBe(fondoAccion);
            report.comprobaciones.push(label+': cabecera con el mismo degradado que su acción');
        }
        await login('propietario');
        await page.getByRole('navigation',{name:'Navegación principal'}).getByRole('link',{name:'Tipos de muebles',exact:true}).click();
        await expect(page.getByRole('heading',{name:'Tipos de muebles',exact:true})).toBeVisible();
        async function revisar(nombre) {
            for(const width of [1440,1024,390]) {
                await page.setViewportSize({width,height:1000});
                await noOverflow(nombre+' '+width);
                await capture(nombre+'-'+width);
            }
            await page.locator('[data-theme-toggle]').click();
            await noOverflow(nombre+' oscuro móvil');
            await capture(nombre+'-oscuro-390');
            await page.locator('[data-theme-toggle]').click();
            await page.setViewportSize({width:1440,height:1000});
        }
        async function cerrarAviso() {
            await expect(page.locator('.swal2-popup')).toBeVisible();
            await page.evaluate(()=>Swal.close());
        }
        await revisar('listado');
        await page.getByRole('link',{name:'Registrar tipo de mueble',exact:true}).click();
        await expect(page.locator('input[name="codigo"]')).toHaveCount(0);
        await page.locator('#nombre').fill('Cómoda');
        await revisar('crear');
        await page.getByRole('button',{name:'Registrar tipo de mueble',exact:true}).click();
        await cerrarAviso();
        await page.locator('input[name="q"]').fill('Cómoda');
        await page.getByRole('button',{name:'Buscar',exact:true}).click();
        await expect(page.locator('.table-list-column tbody tr')).toHaveCount(1);
        await page.getByRole('link',{name:'Ver',exact:true}).click();
        await expect(page.getByRole('heading',{name:'Información del tipo de mueble'})).toBeVisible();
        await expect(page.getByText('Propietario de prueba',{exact:true}).last()).toBeVisible();
        await revisar('ver');
        await page.getByRole('link',{name:'Editar',exact:true}).click();
        await page.locator('#activo').selectOption('0');
        await revisar('editar');
        await page.getByRole('button',{name:'Actualizar tipo de mueble',exact:true}).click();
        await cerrarAviso();
        const urlEdit=await page.getByRole('row').filter({hasText:'Cómoda'}).getByRole('link',{name:'Editar',exact:true}).getAttribute('href');
        await expect(page.getByRole('row').filter({hasText:'Cómoda'})).toContainText('Inactivo');
        await page.goto(base+'/admin/cotizaciones/create');
        await expect(page.locator('#d-0-tipo_mueble_id option').filter({hasText:'Cómoda'})).toHaveCount(0);
        await page.goto(urlEdit);
        await page.locator('#activo').selectOption('1');
        await page.getByRole('button',{name:'Actualizar tipo de mueble',exact:true}).click();
        await cerrarAviso();
        await page.goto(base+'/admin/cotizaciones/create');
        const nuevo=page.locator('#d-0-tipo_mueble_id option').filter({hasText:'Cómoda'});
        await expect(nuevo).toHaveCount(1);
        await page.locator('#d-0-tipo_mueble_id').selectOption(await nuevo.getAttribute('value'));
        report.comprobaciones.push('Registro, búsqueda, ficha con responsables, desactivación/reactivación y selección automática en cotización');
        const datos=JSON.parse(fs.readFileSync(path.join(dir,'datos4a.json'),'utf8'));
        await page.locator('#d-0-material_principal_id').selectOption(String(datos.material));
        await page.locator('#d-0-largo_cm').fill('100');
        await page.locator('#d-0-ancho_cm').fill('45');
        await page.locator('#d-0-alto_cm').fill('90');
        await page.locator('#d-0-acabado').selectOption('Barnizado natural');
        await page.locator('[data-llenado-manual]').click();
        await page.locator('[data-cantidad-unitaria]').first().fill('2');
        await page.locator('[data-mano-unitaria]').fill('20');
        await page.locator('[data-otros-costos]').fill('5');
        await page.getByRole('button',{name:'Registrar cotización',exact:true}).click();
        await cerrarAviso();
        await expect(page.locator('[id^="mueble-"] .record-heading h2')).toContainText('Cómoda');
        await expect(page.locator('.record-field').filter({hasText:'Costo total aplicado'})).toContainText('105,00');
        await capture('cotizacion-con-tipo-nuevo');
        report.comprobaciones.push('Cotización manual guardada con el nuevo tipo y costo calculado Bs 105');

        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await login('maestro');
        await page.goto(base+'/admin/tipos-mueble?q=Cómoda');
        await expect(page.locator('.table-list-column tbody tr')).toHaveCount(1);
        await expect(page.getByRole('link',{name:'Registrar tipo de mueble',exact:true})).toHaveCount(0);
        await expect(page.getByRole('link',{name:'Editar',exact:true})).toHaveCount(0);
        const response=await context.request.get(base+'/admin/tipos-mueble/create');
        expect(response.status()).toBe(403);
        await page.getByRole('link',{name:'Ver',exact:true}).click();
        await expect(page.getByRole('heading',{name:'Información del tipo de mueble'})).toBeVisible();
        await capture('consulta-maestro');
        report.comprobaciones.push('Maestro consulta fichas y búsqueda, sin controles de administración ni acceso por URL');
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
