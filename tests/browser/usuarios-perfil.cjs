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
    const output = process.env.TAJIBO_CAPTURAS || path.join(root, "docs/capturas/usuarios-perfil");
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
        async function revisarVistaUsuarios(nombre) {
            for (const width of [1440, 1024, 390]) {
                await page.setViewportSize({width, height:1000});
                await noOverflow('Usuarios '+nombre+' '+width);
                await expect(page.locator('.page-heading-refined')).toBeVisible();
                if (width === 1440 && ['crear', 'editar'].includes(nombre)) {
                    const formulario = await page.locator('.usuarios-form-column').boundingBox();
                    const contenido = await page.locator('.content > .container-fluid').boundingBox();
                    expect(formulario.width).toBeLessThan(contenido.width);
                }
                if (nombre === 'ver') {
                    await expect(page.locator('.record-fields-boxed .record-value-icon')).toHaveCount(7);
                    const informacion = await page.locator('.record-detail-cards > div').first().boundingBox();
                    const responsables = await page.locator('.record-detail-cards > div').last().boundingBox();
                    if (width >= 1200) {
                        expect(Math.abs(informacion.y - responsables.y)).toBeLessThan(2);
                        expect(responsables.x).toBeGreaterThanOrEqual(informacion.x + informacion.width - 2);
                    } else {
                        expect(responsables.y).toBeGreaterThanOrEqual(informacion.y + informacion.height - 2);
                    }
                }
                await capture('usuarios-'+nombre+'-'+width);
            }
            await page.locator('[data-theme-toggle]').click();
            await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
            await noOverflow('Usuarios '+nombre+' oscuro móvil');
            await capture('usuarios-'+nombre+'-oscuro-390');
            await page.locator('[data-theme-toggle]').click();
            await page.setViewportSize({width:1440, height:1000});
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
        async function cerrarAviso(){
            await expect(page.locator(".swal2-popup")).toBeVisible();
            await page.evaluate(()=>window.Swal?.close());
        }
        await login("propietario");
        await abrirOpcionesCuenta(page);
        await page.getByRole("link", {name:"Mi perfil",exact:true}).click();
        await expect(page.getByRole("heading",{name:"Mi perfil",exact:true})).toBeVisible();
        report.comprobaciones.push("Cuenta anterior accede a Mi perfil sin cambio forzado ni datos inventados");
        await page.goto(base+"/admin/usuarios/create");
        await revisarVistaUsuarios("crear");
        await expect(page.locator('#rol option[value="propietario"]')).toHaveCount(0);
        await expect(page.locator('#rol option[value="maestro"]')).toHaveCount(1);
        await expect(page.locator('input[name="password"]')).toHaveCount(0);
        await expect(page.locator('select[name="activo"]')).toHaveCount(0);
        await page.getByLabel("Nombre completo",{exact:false}).fill("Persona de prueba perfil");
        await page.getByLabel("Correo electrónico",{exact:false}).fill("personal@example.test");
        await page.getByLabel("Tipo de identificación",{exact:false}).selectOption("ci");
        await page.getByLabel("Número de identificación",{exact:false}).fill("90004100");
        await page.getByLabel("Teléfono",{exact:false}).fill("70000000");
        await page.getByLabel("Dirección",{exact:false}).fill("Dirección de prueba aislada");
        await noOverflow("Registro usuario escritorio");
        await capture("registro-usuario");
        await page.getByRole("button",{name:"Crear cuenta",exact:true}).click();
        await expect(page).toHaveURL(base+"/admin/usuarios");
        await expect(page.locator(".swal2-popup")).toContainText("cuenta se creó");
        await cerrarAviso();
        await revisarVistaUsuarios("listado");
        const fila=page.locator(".content-wrapper tbody tr").filter({hasText:"personal@example.test"});
        await fila.getByRole("link",{name:"Ver",exact:true}).click();
        await expect(page.getByText("CI · 90004100",{exact:true})).toBeVisible();
        await expect(page.getByText("70000000",{exact:true})).toBeVisible();
        await revisarVistaUsuarios("ver");
        await capture("ficha-personal");
        report.comprobaciones.push("Propietario registra datos personales sin contraseña manual; ficha muestra identificación y contacto");
        await page.getByRole("link",{name:"Editar",exact:true}).click();
        await expect(page.locator('input[name="password"]')).toHaveCount(0);
        await expect(page.getByLabel("Estado",{exact:true})).toBeVisible();
        await revisarVistaUsuarios("editar");
        await expect(page.locator('#rol option[value="propietario"]')).toHaveCount(0);
        report.comprobaciones.push("Propietario oculto al crear y editar maestros cuando ya existe una cuenta propietaria");
        await page.getByLabel("Teléfono",{exact:false}).fill("71111111");
        await page.getByRole("button",{name:"Guardar cambios",exact:true}).click();
        await cerrarAviso();
        report.comprobaciones.push("Edición administrativa conserva contraseña inicial y permite estado/contacto");
        await abrirOpcionesCuenta(page);
        await page.getByRole("button",{name:"Salir",exact:true}).click();
        await page.getByLabel("Correo electrónico",{exact:true}).fill("personal@example.test");
        await page.getByLabel("Contraseña",{exact:true}).fill("tj.90004100");
        await page.getByRole("button",{name:"Entrar al panel",exact:true}).click();
        await expect(page).toHaveURL(base+"/admin/perfil");
        await expect(page.getByText("Cambia tu contraseña inicial para continuar.",{exact:true})).toBeVisible();
        await expect(page.locator('input[name="name"]')).toHaveCount(0);
        await capture("primer-acceso");
        await page.goto(base+"/admin/materiales");
        await expect(page).toHaveURL(base+"/admin/perfil");
        report.comprobaciones.push("Acceso inicial con prefijo tj. obliga a cambiar contraseña antes de consultar otros módulos");
        await page.getByLabel("Contraseña actual",{exact:false}).fill("incorrecta");
        const nueva="Clave-Personal-De-Prueba-2026!";
        await page.locator("#password").fill(nueva);
        await page.getByLabel("Repetir nueva contraseña",{exact:false}).fill(nueva);
        await page.getByRole("button",{name:"Actualizar contraseña",exact:true}).click();
        await expect(page.getByText("La contraseña actual no es correcta.",{exact:false}).first()).toBeVisible();
        await page.getByLabel("Contraseña actual",{exact:false}).fill("tj.90004100");
        await page.locator("#password").fill(nueva);
        await page.getByLabel("Repetir nueva contraseña",{exact:false}).fill(nueva);
        await page.getByRole("button",{name:"Actualizar contraseña",exact:true}).click();
        await expect(page.locator(".swal2-popup")).toContainText("contraseña se actualizó");
        await cerrarAviso();
        await expect(page.getByLabel("Nombre completo",{exact:false})).toBeVisible();
        report.comprobaciones.push("Cambio exige contraseña actual, confirma la nueva y habilita el perfil normal");
        await page.getByLabel("Tipo de identificación",{exact:false}).selectOption('pasaporte');
        await page.getByLabel("Número de identificación",{exact:false}).fill('bo-009100');
        await page.getByLabel("Nombre completo",{exact:false}).fill("Persona actualizada de prueba");
        await page.getByLabel("Correo electrónico",{exact:false}).fill("personal.actualizado@example.test");
        await page.getByLabel("Teléfono",{exact:false}).fill("72222222");
        await page.getByLabel("Dirección",{exact:false}).fill("Nueva dirección de prueba");
        await page.getByRole("button",{name:"Actualizar perfil",exact:true}).click();
        await expect(page.locator(".swal2-popup")).toContainText("perfil se actualizó");
        await cerrarAviso();
        await expect(page.getByLabel("Teléfono",{exact:false})).toHaveValue("72222222");
        await expect(page.getByLabel("Tipo de identificación",{exact:false})).toHaveValue('pasaporte');
        await expect(page.getByLabel("Número de identificación",{exact:false})).toHaveValue('BO-009100');
        for(const campo of ["rol","activo"]){await expect(page.locator('[name="'+campo+'"]').filter({visible:true})).toHaveCount(0);}
        const token=await page.locator('input[name="_token"]').first().inputValue();
        const inyeccion=await context.request.put(base+"/admin/perfil",{headers:{Accept:"application/json"},form:{_token:token,name:"Persona actualizada de prueba",email:"personal.actualizado@example.test",rol:"propietario"}});
        expect(inyeccion.status()).toBe(422);
        expect((await context.request.get(base+"/admin/usuarios")).status()).toBe(403);
        report.comprobaciones.push("Maestro modifica datos propios e identificación normalizada; roles y estado siguen protegidos incluso por petición directa");
        for(const [width,height] of [[1440,1000],[1024,768],[390,844]]){await page.setViewportSize({width,height});await noOverflow("Perfil "+width);
            await expect(page.locator('.profile-data-column > section.card')).toHaveCount(1);
            const datos = await page.locator('.profile-data-column').boundingBox();
            const contrasena = await page.locator('.profile-password-column').boundingBox();
            await expect(page.locator('.profile-data-column').getByRole('heading', {name:'Datos personales y de usuario', exact:true})).toBeVisible();
            await expect(page.locator('.profile-data-column').getByRole('heading', {name:'Datos de usuario', exact:true})).toBeVisible();
            if (width >= 992) {
                expect(Math.abs(datos.y - contrasena.y)).toBeLessThan(2);
                expect(contrasena.x).toBeGreaterThanOrEqual(datos.x + datos.width - 2);
            } else {
                expect(contrasena.y).toBeGreaterThanOrEqual(datos.y + datos.height - 2);
            }
            expect(await page.locator('#email').evaluate(input => input.form?.id)).toBe('perfil-datos');
            expect(await page.getByRole('button', {name:'Actualizar perfil', exact:true}).evaluate(button => button.form?.id)).toBe('perfil-datos');
            report.comprobaciones.push('Perfil '+width+': datos personales y cuenta en una columna, contraseña en otra y formularios asociados correctamente');
            await capture("perfil-"+width);}
        await page.setViewportSize({width:1440,height:1000});
        await abrirOpcionesCuenta(page);
        await page.getByRole("button",{name:"Salir",exact:true}).click();
        await page.getByLabel("Correo electrónico",{exact:true}).fill("personal.actualizado@example.test");
        await page.getByLabel("Contraseña",{exact:true}).fill("tj.90004100");
        await page.getByRole("button",{name:"Entrar al panel",exact:true}).click();
        await expect(page).toHaveURL(base+"/login");
        await page.getByLabel("Contraseña",{exact:true}).fill(nueva);
        await page.getByRole("button",{name:"Entrar al panel",exact:true}).click();
        await expect(page).toHaveURL(base+"/admin");
        await abrirOpcionesCuenta(page);
        await page.getByRole("link",{name:"Mi perfil",exact:true}).click();
        await expect(page.getByLabel("Correo electrónico",{exact:false})).toHaveValue("personal.actualizado@example.test");
        report.comprobaciones.push("Clave inicial deja de funcionar y nuevo correo/contraseña permiten acceso normal y enlace Mi perfil");
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
