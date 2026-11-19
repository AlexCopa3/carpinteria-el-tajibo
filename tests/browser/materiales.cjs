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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/materiales-2026-11-10");
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
        async function vistas(url,nombre,formulario=false){
            await page.goto(url.startsWith('http')?url:base+url);
            for(const width of [1440,1024,390]){await page.setViewportSize({width,height:1000});await noOverflow(nombre+' '+width);await capture(nombre+'-'+width);}
            await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();await noOverflow(nombre+' oscuro');await capture(nombre+'-oscuro-390');
            await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();if(formulario)await verificarCabecera(nombre);
        }
        await vistas('/admin/materiales','materiales-index');
        await expect(page.locator('.content-wrapper tbody a strong').first()).toBeVisible();
        await vistas('/admin/materiales/create','materiales-create',true);
        await expect(page.locator('input[name="codigo"]')).toHaveCount(0);await expect(page.locator('[name="stock_minimo"]')).toHaveCount(0);
        await page.setViewportSize({width:1440,height:1000});
        const filas=await page.locator('#unidad_id,#precio_referencia,#activo').evaluateAll(els=>els.map(el=>el.getBoundingClientRect().top));expect(Math.max(...filas)-Math.min(...filas)).toBeLessThan(2);
        await page.locator('#nombre').fill('Tornillos para muebles');await page.locator('#categoria_id').selectOption({label:'Accesorios de prueba'});
        await page.locator('#descripcion').fill('Tornillos de uso general en el taller.');await page.locator('#especificaciones').fill('Caja cerrada, referencia del catálogo.');
        const unidad=await page.locator('#unidad_id option').filter({hasText:'solo enteros'}).first().getAttribute('value');await page.locator('#unidad_id').selectOption(unidad);
        await page.locator('#precio_referencia').fill('50.25');
        await page.getByRole('button',{name:'Guardar material',exact:true}).click();
        await expect(page).toHaveURL(/\/admin\/materiales\/\d+$/);const show=page.url();
        await expect(page.locator('.swal2-popup')).toContainText('Material registrado correctamente.');await page.evaluate(()=>Swal.close());
        await vistas(show,'materiales-show');
        await expect(page.getByText('Responsables del registro',{exact:true})).toBeVisible();await expect(page.getByText('Referencia por unidad',{exact:true})).toBeVisible();
        await page.setViewportSize({width:1440,height:1000});
        const campos=await page.locator('.record-fields .record-field').evaluateAll(els=>els.filter(el=>['Categoría','Unidad de medida'].includes(el.querySelector('dt').textContent.trim())).map(el=>el.getBoundingClientRect().top));expect(Math.abs(campos[0]-campos[1])).toBeLessThan(2);
        await page.getByRole('link',{name:'Ver existencias',exact:true}).click();await expect(page).toHaveURL(/\/admin\/existencias\?material_id=/);
        await vistas(show+'/edit','materiales-edit',true);
        await expect(page.locator('#nombre')).toHaveValue('Tornillos para muebles');await expect(page.getByText('Responsables del registro',{exact:true})).toHaveCount(0);
        await page.locator('#nombre').fill('Tornillos para muebles actualizados');await page.locator('#precio_referencia').fill('55.50');await page.locator('#activo').selectOption('0');
        await page.getByRole('button',{name:'Actualizar material',exact:true}).click();await expect(page.locator('.swal2-popup')).toContainText('Material actualizado correctamente.');await page.evaluate(()=>Swal.close());
        await expect(page.getByRole('heading',{name:'Tornillos para muebles actualizados',exact:true})).toBeVisible();
        await page.goto(show+'/edit');await page.locator('#precio_referencia').fill('-1');
        const datos=await page.locator('form').filter({has:page.locator('#nombre')}).evaluate(el=>Object.fromEntries(new FormData(el)));
        expect((await context.request.post(show,{form:datos,headers:{Accept:'application/json'},maxRedirects:0})).status()).toBe(422);
        await page.goto(base+'/admin/materiales?q=Tornillos&estado=inactivo');await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(1);await expect(page.locator('.content-wrapper tbody')).toContainText('Tornillos para muebles actualizados');
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();await login('maestro');
        await page.goto(base+'/admin/materiales');await expect(page.getByRole('link',{name:'Nuevo material',exact:true})).toHaveCount(0);await expect(page.locator('.content-wrapper tbody').getByRole('link',{name:'Editar',exact:true})).toHaveCount(0);await expect(page.locator('.content-wrapper tbody').getByRole('button',{name:'Eliminar',exact:true})).toHaveCount(0);
        await page.goto(show);await expect(page.getByText('Referencia por unidad',{exact:true})).toBeVisible();await expect(page.getByRole('link',{name:'Editar material',exact:true})).toHaveCount(0);await capture('materiales-maestro');
        expect((await context.request.get(show+'/edit',{headers:{Accept:'application/json'}})).status()).toBe(403);
        report.comprobaciones.push('Materiales: lista azul/negrita, alta, edición, consulta, costo visible al maestro sin administración, tres campos alineados, ficha en dos columnas, errores del servidor, búsqueda/estado y enlace a existencias');
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
