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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/categorias-version2-2026-11-10");
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
        const datos=JSON.parse(fs.readFileSync(path.join(dir,'datos4a.json'),'utf8'));
        async function vistas(url,nombre,formulario=false){
            await page.goto(base+url);
            for(const width of [1440,1024,390]){
                await page.setViewportSize({width,height:1000});await noOverflow(nombre+' '+width);if(formulario && width===1440){const tarjeta=await page.locator('.form-panel').boundingBox();expect(tarjeta.width).toBeLessThan(900);report.comprobaciones.push(nombre+': ancho compacto en escritorio');}await capture(nombre+'-'+width);
            }
            await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();
            await noOverflow(nombre+' oscuro');await capture(nombre+'-oscuro-390');
            await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();
            if(formulario)await verificarCabecera(nombre);
        }
        await vistas('/admin/categorias','categorias-index');
        await page.locator('#q').fill('Material principal');await page.getByRole('button',{name:'Buscar',exact:true}).click();
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(1);await expect(page.locator('.content-wrapper tbody')).toContainText('Material principal');
        await expect(page.locator('#q')).toHaveValue('Material principal');await noOverflow('Categorías búsqueda móvil');await capture('categorias-busqueda');
        await page.getByRole('link',{name:'Limpiar',exact:true}).click();await expect(page.locator('#q')).toHaveValue('');await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(2);
        await page.locator('#q').fill('Sin coincidencia');await page.getByRole('button',{name:'Buscar',exact:true}).click();
        await expect(page.getByText('No se encontraron categorías',{exact:true})).toBeVisible();await capture('categorias-sin-resultados');
        await page.getByRole('link',{name:'Limpiar',exact:true}).click();
        report.comprobaciones.push('Categorías: búsqueda por nombre, texto conservado, limpiar y sin coincidencias desde la interfaz');
        await vistas('/admin/categorias/create','categorias-create',true);
        await expect(page.locator('#activo')).toHaveValue('1');
        await expect(page.locator('form input:not([type="hidden"]), form select').filter({visible:true})).toHaveCount(2);
        await page.locator('#nombre').fill('Herrajes del taller');
        await page.getByRole('button',{name:'Guardar categoría',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/categorias');
        await expect(page.locator('.swal2-popup')).toContainText('Categoría registrada.');await page.evaluate(()=>Swal.close());
        const fila=page.locator('.content-wrapper tbody tr').filter({hasText:'Herrajes del taller'});
        const show=await fila.getByRole('link',{name:'Ver',exact:true}).getAttribute('href');
        const edit=await fila.getByRole('link',{name:'Editar',exact:true}).getAttribute('href');
        await vistas(new URL(show).pathname,'categorias-show-vacia');
        await expect(page.getByText('No hay materiales relacionados.',{exact:true})).toBeVisible();
        await expect(page.getByRole('region',{name:'Responsables del registro',exact:true})).toBeVisible();
        await vistas(new URL(edit).pathname,'categorias-edit',true);
        await expect(page.locator('#nombre')).toHaveValue('Herrajes del taller');
        await expect(page.getByText('Responsables del registro',{exact:true})).toHaveCount(0);
        await page.locator('#nombre').fill('Herrajes y accesorios');await page.locator('#activo').selectOption('0');
        await page.getByRole('button',{name:'Actualizar categoría',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Categoría actualizada.');await page.evaluate(()=>Swal.close());
        await expect(page.locator('.content-wrapper tbody tr').filter({hasText:'Herrajes y accesorios'}).locator('.record-status')).toHaveText('Inactivo');
        await expect(page.locator('.content-wrapper .estado-registro i')).toHaveCount(await page.locator('.content-wrapper .estado-registro').count());
        await expect(page.locator('.estado-registro.is-active i').first()).toHaveClass('fas fa-check-circle');
        await expect(page.locator('.estado-registro.is-inactive i').first()).toHaveClass('fas fa-minus-circle');
        expect(await page.locator('.estado-registro.is-inactive').first().evaluate(el => getComputedStyle(el).backgroundColor)).toBe('rgb(89, 99, 111)');
        expect(await page.locator('.estado-registro.is-inactive').first().evaluate(el => getComputedStyle(el).color)).toBe('rgb(255, 255, 255)');
        expect(await page.locator('.estado-registro.is-active').first().evaluate(el => getComputedStyle(el).backgroundColor)).toBe('rgb(132, 223, 195)');
        report.comprobaciones.push('Estado: iconos de confirmación/menos; Activo menta e Inactivo plomo oscuro con texto blanco');
        await page.goto(base+'/admin/categorias/create');await page.locator('#nombre').fill('Herrajes y accesorios');
        await page.getByRole('button',{name:'Guardar categoría',exact:true}).click();
        await expect(page.locator('#nombre-error')).toBeVisible();await expect(page.locator('#nombre')).toHaveValue('Herrajes y accesorios');
        await expect(page.locator('#nombre')).toHaveAttribute('aria-invalid','true');await capture('categorias-validacion');
        await page.goto(base+'/admin/categorias');
        const principal=page.locator('.content-wrapper tbody tr').filter({hasText:'Material principal'});
        const principalUrl=await principal.getByRole('link',{name:'Ver',exact:true}).getAttribute('href');
        await vistas(new URL(principalUrl).pathname,'categorias-show-materiales');
        await expect(page.locator('.table tbody tr')).toHaveCount(2);
        const materialLink=page.locator('.table').getByRole('link',{name:'Ver',exact:true}).first();
        await materialLink.click();await expect(page).toHaveURL(/\/admin\/materiales\/\d+$/);
        await page.goto(base+'/admin/categorias/create');
        const alta=await page.locator('form').filter({has:page.locator('#nombre')}).evaluate(el=>Object.fromEntries(new FormData(el)));
        for(let i=0;i<12;i++)expect((await context.request.post(base+'/admin/categorias/create',{form:{...alta,nombre:'Categoría de prueba '+String(i).padStart(2,'0')},maxRedirects:0})).status()).toBe(302);
        await page.goto(base+'/admin/categorias');await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(12);
        const filas=page.locator('.content-wrapper table tbody tr');
        for(let i=0;i<await filas.count();i++){
            const botones=filas.nth(i).locator('.table-actions .btn');const boxes=await botones.evaluateAll(elements=>elements.map(el=>{const r=el.getBoundingClientRect();return {y:r.y,h:r.height}}));
            expect(Math.max(...boxes.map(x=>x.y+x.h/2))-Math.min(...boxes.map(x=>x.y+x.h/2))).toBeLessThan(2);
        }
        await page.goto(base+'/admin/categorias?page=2');await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(3);await capture('categorias-pagina-2');
        const borrar=page.locator('.content-wrapper tbody tr').filter({hasText:'Herrajes y accesorios'}).getByRole('button',{name:'Eliminar',exact:true});
        await borrar.click();await expect(page.locator('.swal2-popup')).toBeVisible();await capture('categorias-confirmacion-eliminar');await page.locator('.swal2-cancel').click();
        await expect(page.locator('.content-wrapper tbody tr').filter({hasText:'Herrajes y accesorios'})).toBeVisible();
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();await login('maestro');
        await page.goto(base+'/admin/categorias');await expect(page.getByRole('link',{name:'Registrar categoría',exact:true})).toHaveCount(0);
        await expect(page.locator('.content-wrapper tbody').getByRole('link',{name:'Editar',exact:true})).toHaveCount(0);await expect(page.locator('.content-wrapper tbody').getByRole('button',{name:'Eliminar',exact:true})).toHaveCount(0);
        await page.goto(show);await expect(page.getByRole('link',{name:'Editar',exact:true})).toHaveCount(0);
        expect((await context.request.get(edit,{headers:{Accept:'application/json'}})).status()).toBe(403);
        report.comprobaciones.push('Registro, edición inactiva, consulta, auditoría solo en Ver, validación con datos conservados, materiales relacionados, paginación de 12, botones alineados y cancelación de SweetAlert2; maestro solo consulta');
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
