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
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/actual");
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
        const datos = JSON.parse(fs.readFileSync(path.join(dir, "datos4a.json"), "utf8"));
        await login("propietario");
        // Catálogo simplificado: cotizar no requiere gestionar existencias.
        const menu = page.getByRole("navigation", {name:"Navegación principal"});
        await expect(menu.getByRole("link", {name:"Inventario",exact:true})).toHaveCount(0);
        await expect(page.getByText("Materiales sin existencias",{exact:true})).toHaveCount(0);
        report.comprobaciones.push("Menú y panel sin módulo independiente de inventario ni alertas de faltantes");
        for (const [width,height] of [[1440,1000],[1024,768],[390,844]]) {
            await page.setViewportSize({width,height});
            await page.goto(base+"/admin/materiales");
            await expect(page.locator(".content-wrapper th").filter({hasText:/^Existencia$/})).toHaveCount(0);
            await noOverflow("Catálogo simplificado "+width);
            await capture("catalogo-"+width);
            await page.goto(base+"/admin/materiales/create");
            await expect(page.locator('[name="stock_minimo"]')).toHaveCount(0);
            await expect(page.getByLabel("Unidad de medida",{exact:true})).toBeVisible();
            await noOverflow("Material sin stock "+width);
            await capture("material-sin-stock-"+width);
        }
        await page.setViewportSize({width:1440,height:1000});
        await page.goto(base+"/admin/materiales/"+datos.material);
        await expect(page.locator("dt").filter({hasText:/^Existencia$/})).toHaveCount(0);
        await expect(page.getByText("Ver historial de movimientos",{exact:true})).toHaveCount(0);
        await capture("ficha-material-sin-stock");
        await expect(page.getByRole('link',{name:'Nueva presentación',exact:true})).toHaveCount(0);
        await page.getByRole('link',{name:'Ver existencias',exact:true}).click();
        await expect(page.getByRole('heading',{name:'Existencias de materiales',exact:true})).toBeVisible();
        for (const [width,height] of [[1440,1000],[1024,768],[390,844]]) {
            await page.setViewportSize({width,height});
            await noOverflow('Módulo de existencias '+width);
            await capture('existencias-'+width);
        }
        await page.setViewportSize({width:1440,height:1000});
        await page.getByRole('link',{name:'Agregar cantidad',exact:true}).click();
        await verificarCabecera('Agregar cantidad');
        await page.locator('#cantidad').fill('3');
        await page.locator('#motivo').fill('Material recibido de prueba');
        for(const [width,height] of [[1440,1000],[390,844]]) {
            await page.setViewportSize({width,height});
            await noOverflow('Agregar cantidad '+width);
            await capture('agregar-cantidad-'+width);
        }
        await page.getByRole('button',{name:'Agregar cantidad',exact:true}).click();
        await page.evaluate(()=>window.Swal?.close());
        await expect(page.getByText('3 Metro',{exact:true})).toBeVisible();
        await page.getByRole('link',{name:'Corregir cantidad',exact:true}).click();
        await verificarCabecera('Corregir cantidad');
        await page.locator('#cantidad').fill('1.5');
        await page.locator('#motivo').fill('Recuento físico de prueba');
        await capture('corregir-cantidad-movil');
        await page.getByRole('button',{name:'Corregir cantidad',exact:true}).click();
        await page.evaluate(()=>window.Swal?.close());
        await expect(page.getByText('1,5 Metro',{exact:true})).toBeVisible();
        report.comprobaciones.push('Existencias integradas: agregar 3 y corregir a 1,5; sin presentaciones');
        await page.getByRole('link',{name:'Ver movimientos',exact:true}).click();
        await expect(page.getByRole('heading',{name:'Movimientos del material',exact:true})).toBeVisible();
        await expect(page.getByText('Material recibido de prueba',{exact:true})).toBeVisible();
        await expect(page.getByText('Recuento físico de prueba',{exact:true})).toBeVisible();
        await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(2);
        for(const [width,height] of [[1440,1000],[1024,768],[390,844]]) {
            await page.setViewportSize({width,height});
            await noOverflow('Movimientos por material '+width);
            await capture('movimientos-'+width);
        }
        report.comprobaciones.push('Ver movimientos muestra responsable, motivo y saldos de las dos operaciones');
        await page.getByRole('link',{name:'Volver',exact:true}).click();
        await page.setViewportSize({width:1440,height:1000});
        const retirado=await context.request.get(base+"/admin/inventario");
        expect(retirado.status()).toBe(404);
        report.comprobaciones.push("Ficha con existencia integrada y rutas del módulo anterior retiradas (404)");
        await page.getByRole("navigation", {name:"Navegación principal"}).getByRole("link", {name:"Cotizaciones",exact:true}).click();
        await expect(page.getByRole("heading", {name:"Cotizaciones",exact:true})).toBeVisible();
        await capture("cotizaciones-inicial");
        await expect(page.getByRole('link',{name:'Recargo predeterminado',exact:true})).toHaveCount(0);
        await page.getByRole("link",{name:"Nueva cotización",exact:true}).click();
        await expect(page.getByRole('heading',{name:'Prepara tu cotización',exact:true})).toBeVisible();
        expect(await page.locator('[data-notas-cotizacion]').evaluate(el => el.open)).toBe(false);
        expect(await page.locator('[data-costos-opcionales]').first().evaluate(el => el.open)).toBe(false);
        expect(await page.locator('[data-componentes-mueble]').first().evaluate(el => el.open)).toBe(false);
        report.comprobaciones.push('Guía de llenado visible; notas, componentes y ajustes opcionales cerrados en un alta nueva');
        for (const width of [1440,390]) {
            await page.setViewportSize({width,height:844});
            await page.evaluate(()=>window.scrollTo(0,document.documentElement.scrollHeight));
            expect(await page.evaluate(()=>window.scrollY)).toBeGreaterThan(100);
            if (width < 992) {
                const abrir = page.getByRole('button',{name:'Abrir o cerrar menú',exact:true});
                await expect(abrir).toBeInViewport();
                await abrir.click();
                await expect(page.locator('body')).toHaveClass(/sidebar-open/);
            }
            const menu = page.locator('#sidebar-menu');
            const rect = await menu.boundingBox();
            expect(Math.abs(rect.y)).toBeLessThan(2);
            expect(Math.abs(rect.height-844)).toBeLessThan(2);
            const scrollPagina = await page.evaluate(()=>window.scrollY);
            await page.locator('#sidebar-menu .sidebar').evaluate(el=>{el.scrollTop=el.scrollHeight;});
            await expect(menu.getByRole('link',{name:'Configuración',exact:true})).toBeInViewport();
            expect(await page.evaluate(()=>window.scrollY)).toBe(scrollPagina);
            await noOverflow('Menú fijo al final de cotización '+width);
            await capture('menu-final-cotizacion-'+width);
            if (width < 992) {
                await page.getByRole('button',{name:'Cerrar menú',exact:true}).click();
                await expect(page.locator('body')).not.toHaveClass(/sidebar-open/);
            }
            await page.locator('#sidebar-menu .sidebar').evaluate(el=>{el.scrollTop=0;});
            await page.evaluate(()=>window.scrollTo(0,0));
        }
        await page.setViewportSize({width:1440,height:1000});
        report.comprobaciones.push('Menú fijo al final del formulario; opciones con desplazamiento independiente y apertura/cierre móvil accesibles');
        await expect(page.locator('#recargo_porcentaje')).toHaveValue('');
        await page.getByLabel('Ganancia sobre el costo (%)',{exact:true}).fill('30');
        report.comprobaciones.push('Sin configuración predeterminada; ganancia introducida solo para esta cotización');
        expect(await page.locator('select[name="detalles[0][acabado]"] option').allTextContents()).toEqual(['Selecciona un acabado','Barnizado natural','Pintado','Melamina','Sin acabado','Otros']);
        report.comprobaciones.push('Acabado ofrece únicamente las cinco opciones aprobadas');
        expect(await page.locator("#cliente_id").count()).toBe(0);
        report.comprobaciones.push("Cotización sin selector de cliente; datos personales reservados para aceptación futura");
        async function llenarDetalle(indice, tipo, material, muebles, cantidad, mano) {
            await page.locator('[data-detalle]').nth(indice).locator('[data-llenado-costos]').evaluate(el => el.open = true);
            const campo=(nombre)=>page.locator('[name="detalles['+indice+']['+nombre+']"]');
            await campo("tipo_mueble_id").selectOption(String(tipo));
            await campo("cantidad_muebles").fill(String(muebles));
            await campo("material_principal_id").selectOption(String(material));
            for(const [nombre,valor] of Object.entries({largo_cm:"120",ancho_cm:"60",alto_cm:"75"}))await campo(nombre).fill(valor);
            await campo("acabado").selectOption("Barnizado natural");
            await page.locator('[name="detalles['+indice+'][materiales][0][cantidad_unitaria]"]').fill(String(cantidad));
            await campo("mano_obra_unitaria").fill(String(mano));
        }
        await expect(page.locator('[name="detalles[0][material_principal_id]"] option[value="'+datos.secundario+'"]')).toHaveCount(0);
        await expect(page.locator('[name="detalles[0][materiales][0][material_id]"] option[value="'+datos.secundario+'"]')).toHaveCount(1);
        report.comprobaciones.push("Selector principal excluye accesorios; la lista de costos permite utilizarlos");
        await llenarDetalle(0,datos.mesa,datos.material,3,2,20);
        const primerDetalle = page.locator('[data-detalle]').first();
        await primerDetalle.locator('[data-componentes-mueble] summary').click();
        await page.locator('[name="detalles[0][cajones]"]').fill('-1');
        await primerDetalle.locator('[data-componentes-mueble] summary').click();
        await page.getByRole('button',{name:'Registrar cotización',exact:true}).click();
        expect(await primerDetalle.locator('[data-componentes-mueble]').evaluate(el => el.open)).toBe(true);
        await page.locator('[name="detalles[0][cajones]"]').fill('0');
        await primerDetalle.locator('[data-componentes-mueble] summary').click();
        report.comprobaciones.push('Validación nativa abre una sección cerrada con campo inválido; corrección accesible sin perder datos');
        await expect(primerDetalle.locator('[data-material-row]')).toHaveCount(1);
        await expect(primerDetalle.locator('[data-material-select]')).toHaveValue(String(datos.material));
        await expect(primerDetalle.locator('[data-costo-material]')).toHaveValue('40');
        await page.locator('[name="detalles[0][material_principal_id]"]').dispatchEvent('change');
        await expect(primerDetalle.locator('[data-material-row]')).toHaveCount(1);
        await primerDetalle.getByRole('button', {name:'Añadir material',exact:true}).click();
        const accesorio = primerDetalle.locator('[data-material-row]').last();
        await accesorio.locator('[data-buscar-material]').fill('accesorio');
        await expect(accesorio.locator('[data-busqueda-resultado]')).toHaveText('1 materiales encontrados');
        expect(await accesorio.locator('[data-material-select] option[value="'+datos.material+'"]').evaluate(opcion => opcion.hidden)).toBe(true);
        await accesorio.locator('[data-material-select]').selectOption(String(datos.secundario));
        await accesorio.locator('[data-cantidad-unitaria]').fill('2');
        await expect(accesorio.locator('[data-subtotal-material]')).toHaveText('Bs 30,00');
        await accesorio.getByRole('button',{name:'Quitar',exact:true}).click();
        await expect(primerDetalle.locator('[data-material-row]')).toHaveCount(1);
        report.comprobaciones.push('Material principal automático sin duplicados; búsqueda, unidad, costo y subtotal automáticos; quitar no altera otras filas');
        expect(await primerDetalle.locator('[data-ajuste-material]').evaluate(el => el.open)).toBe(false);
        await primerDetalle.locator('[data-ajuste-material] summary').click();
        await page.locator('[name="detalles[0][materiales][0][cantidad_total_aplicada]"]').fill("5.5");
        await page.locator('[name="detalles[0][materiales][0][motivo_ajuste_cantidad]"]').fill("Ahorro por fabricación conjunta");
        await primerDetalle.locator('[data-costos-opcionales] summary').click();
        await page.locator('[name="detalles[0][mano_obra_total_aplicada]"]').fill("55");
        await page.locator('[name="detalles[0][motivo_ajuste_mano_obra]"]').fill("Preparación conjunta del trabajo");
        await expect(page.locator("[data-total-cotizacion]")).toHaveText("Bs 275,00");
        await expect(page.locator("[data-sugerido-cotizacion]")).toHaveText("Bs 357,50");
        await expect(primerDetalle.locator('[data-total-materiales]')).toHaveText('Bs 220,00');
        await expect(primerDetalle.locator('[data-total-mano]')).toHaveText('Bs 55,00');
        await expect(primerDetalle.locator('[data-total-otros]')).toHaveText('Bs 0,00');
        await expect(primerDetalle.locator('[data-total-detalle]')).toHaveText('Bs 275,00');
        report.comprobaciones.push('Resumen separa materiales, mano de obra, otros costos y total sin cambiar importes');
        report.comprobaciones.push("Vista previa exacta: nominales por unidad y ahorro conjunto, Bs 275 de costo / Bs 357,50 con recargo");
        await page.getByRole("button",{name:"Añadir mueble",exact:true}).click();
        await llenarDetalle(1,datos.silla,datos.material,1,1,10);
        expect(await page.locator('[name="detalles[1][acabado]"]').evaluate(el => el.getAttribute('aria-describedby').trim().split(/\s+/).every(id => document.getElementById(id)))).toBe(true);
        await expect(page.locator("[data-total-cotizacion]")).toHaveText("Bs 325,00");
        for(const width of [1440,1024,390]){
            await page.setViewportSize({width,height:1000});
            await noOverflow("Registro de cotización "+width);
            await capture("cotizacion-formulario-"+width);
        }
        await page.setViewportSize({width:1440,height:1000});
        await page.getByRole("button",{name:"Registrar cotización",exact:true}).click();
        await expect(page).toHaveURL(/\/admin\/cotizaciones\/\d+$/);
        const url=page.url();
        await expect(page.locator(".swal2-popup")).toContainText("Cotización registrada");
        await page.waitForFunction(() => typeof window.Swal !== "undefined");
        await page.evaluate(()=>Swal.close());
        await expect(page.getByText("Mano de obra nominal:",{exact:false}).first()).toBeVisible();
        expect((await context.request.get(url+"/pdf")).status()).toBe(403);
        report.comprobaciones.push("Dos tipos de mueble guardados y PDF bloqueado antes de aprobar");
        await capture("cotizacion-detalle-preliminar");
        await page.getByRole("link",{name:"Editar",exact:true}).click();
        expect(await page.locator('[data-costos-opcionales]').first().evaluate(el => el.open)).toBe(true);
        expect(await page.locator('[data-ajuste-material]').first().evaluate(el => el.open)).toBe(true);
        await page.locator('[data-notas-cotizacion] summary').click();
        await page.locator("#observaciones").fill("Presupuesto con dos tipos de muebles y ahorro justificado");
        await page.getByRole("button",{name:"Actualizar cotización",exact:true}).click();
        await expect(page).toHaveURL(url);
        await page.waitForFunction(() => typeof window.Swal !== "undefined");
        await page.evaluate(()=>Swal.close());
        await expect(page.getByText("Versión 2",{exact:true})).toBeVisible();
        report.comprobaciones.push("Edición conserva materiales, cantidades nominales y ajustes y aumenta la versión");
        const precios=page.locator('input[name^="precios"]');
        await precios.nth(0).fill("350");
        await precios.nth(1).fill("75");
        await page.getByRole("button",{name:"Validar y aprobar",exact:true}).click();
        await expect(page.locator(".swal2-popup")).toContainText("validada y aprobada");
        await page.waitForFunction(() => typeof window.Swal !== "undefined");
        await page.evaluate(()=>Swal.close());
        await expect(page.getByText("Total aprobado: Bs 425,00",{exact:true})).toBeVisible();
        await expect(page.getByRole("link",{name:"Editar",exact:true})).toHaveCount(0);
        report.comprobaciones.push("Validación y aprobación en una acción; Bs 350 + Bs 75 = Bs 425, sin edición posterior");
        const pdf=await context.request.get(url+"/pdf");
        expect(pdf.status()).toBe(200);
        expect(pdf.headers()["content-type"]).toContain("application/pdf");
        const buffer=await pdf.body();
        expect(buffer.subarray(0,5).toString()).toBe("%PDF-");
        fs.writeFileSync(path.join(output,"cotizacion-aprobada.pdf"),buffer);
        report.comprobaciones.push("PDF real de Dompdf con precios aprobados disponible");
        for(const width of [1440,1024,390]){
            await page.setViewportSize({width,height:1000});
            await noOverflow("Ficha aprobada "+width);
            await capture("cotizacion-aprobada-"+width);
        }
        await page.setViewportSize({width:1440,height:1000});
        await page.getByRole("link",{name:"Volver",exact:true}).click();
        await capture("cotizaciones-listado");
        await page.goto(base+'/admin/existencias?material_id='+datos.material);
        await expect(page.getByText('1,5 Metro',{exact:true})).toBeVisible();
        report.comprobaciones.push('Cotizar, editar y aprobar no descuentan la existencia disponible');
        await capture('material-despues-de-cotizar');
        await page.goto(url);
        await capture('cotizacion-antes-pedido');
        if(await page.getByRole('link',{name:'Crear pedido',exact:true}).count()===0) throw new Error('Falta Crear pedido: '+await page.locator('body').innerText());
        await page.getByRole('link',{name:'Crear pedido',exact:true}).click();
        await page.getByLabel('Cliente',{exact:true}).selectOption(String(datos.cliente));
        await expect(page.locator('[data-cliente-nuevo]')).toBeHidden();
        await page.locator('label[for="aceptacion"]').click();
        await expect(page.locator('#aceptacion')).toBeChecked();
        for (const width of [1440,1024,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Aceptar pedido '+width); await capture('pedido-create-'+width); }
        await page.getByRole('button',{name:'Registrar pedido',exact:true}).click();
        await expect(page).toHaveURL(/\/admin\/pedidos\/\d+$/);
        const pedidoUrl=page.url();
        const pedidoId=pedidoUrl.split('/').pop();
        expect((await context.request.get(pedidoUrl+'/fabricacion/edit')).status()).toBe(403);
        await expect(page.locator('.swal2-popup')).toContainText('Pedido registrado');
        await page.evaluate(()=>Swal.close());
        await expect(page.getByText('Bs 425,00',{exact:true}).first()).toBeVisible();
        await page.goto(url);
        await page.getByRole('link',{name:'Ver pedido',exact:true}).click();
        await expect(page).toHaveURL(pedidoUrl);
        await page.getByRole('link',{name:'Planificar materiales',exact:true}).click();
        await expect(page.getByRole('heading',{name:'Resumen de todo el pedido'})).toBeVisible();
        const resumen=page.locator('.content-wrapper table').first().locator('tbody tr');
        await expect(resumen).toHaveCount(1);
        await expect(resumen).toContainText('6,5');
        await page.getByRole('link',{name:'Editar materiales',exact:true}).click();
        await page.locator('[name="materiales[0][cantidad_planificada]"]').fill('6');
        await page.getByLabel('Motivo de los cambios *',{exact:true}).fill('Revisión de corte en prueba aislada');
        await page.getByRole('button',{name:'Actualizar materiales',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Planificación actualizada');
        await page.evaluate(()=>Swal.close());
        for (const width of [1440,1024,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Planificación '+width); await capture('planificacion-'+width); }
        const planificacionUrl = page.url();
        await page.getByRole('link',{name:'Volver al listado',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/planificacion');
        for (const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Listado planificación '+width); await capture('planificacion-listado-'+width); }
        await page.goto(planificacionUrl);
        await page.getByRole('link',{name:'Editar materiales',exact:true}).click();
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/planificacion');
        await page.goto(planificacionUrl);
        await page.getByRole('link',{name:'Ver pedido',exact:true}).click();
        await expect(page).toHaveURL(pedidoUrl);
        await page.getByRole('link',{name:'Volver al listado',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/pedidos');
        for (const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Listado pedidos '+width); await capture('pedidos-listado-'+width); }
        await page.goto(pedidoUrl+'/edit');
        await page.getByRole('link',{name:'Cancelar',exact:true}).click();
        await expect(page).toHaveURL(base+'/admin/pedidos');
        report.comprobaciones.push('Volver y Cancelar regresan al listado de cada módulo; Ver pedido abre explícitamente su detalle');
        await page.goto(pedidoUrl);
        await page.getByRole('link',{name:'Editar datos del pedido',exact:true}).click();
        await page.getByLabel('Observaciones operativas (opcional)',{exact:true}).fill('Seguimiento de pedido aislado');
        await page.getByRole('button',{name:'Actualizar pedido',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Pedido actualizado');
        await page.evaluate(()=>Swal.close());
        report.comprobaciones.push('Aceptación con cliente existente, enlace sin duplicado, precio conservado, planificación agrupada y edición de pedido');
        await abrirOpcionesCuenta(page);
        await page.getByRole("button",{name:"Salir",exact:true}).click();
        await login("maestro");
        await page.goto(url);
        await expect(page.getByText("Ganancia sobre el costo:",{exact:false})).toHaveCount(0);
        await expect(page.getByRole("button",{name:"Validar y aprobar",exact:true})).toHaveCount(0);
        expect((await context.request.get(url+"/edit")).status()).toBe(403);
        expect((await context.request.get(base+"/admin/cotizaciones/configuracion")).status()).toBe(404);
        expect((await context.request.get(url+"/pdf")).status()).toBe(200);
        await capture("cotizacion-maestro");
        report.comprobaciones.push("Maestro consulta costos y PDF aprobado sin recargo, aprobación, edición aprobada ni configuración");
        await page.goto(base+"/admin/cotizaciones/create");
        await expect(page.locator('[name="recargo_porcentaje"]')).toHaveCount(0);
        await llenarDetalle(0,datos.mesa,datos.pendiente,1,1,0);
        await expect(page.locator("[data-costo-material]").first()).toBeEnabled();
        await page.locator('[data-costo-material]').first().fill('12.50');
        await expect(page.locator('[data-estado-costo]').first()).toHaveValue('conocido');
        await expect(page.locator('[data-fuente-costo]').first()).toHaveValue('estimacion_manual');
        await expect(page.locator('[data-total-cotizacion]')).toHaveText('Bs 12,50');
        await page.locator('[data-costo-material]').first().fill('');
        await expect(page.locator('[data-total-cotizacion]')).toHaveText('Pendiente de revisión');
        report.comprobaciones.push('Costo pendiente editable: ingresar importe activa costo conocido; vaciarlo recupera pendiente, sin asumir cero');
        await expect(page.locator("[data-estado-costo]").first()).toHaveValue("pendiente");
        await page.getByRole("button",{name:"Registrar cotización",exact:true}).click();
        await expect(page).toHaveURL(/\/admin\/cotizaciones\/\d+$/);
        await page.waitForFunction(() => typeof window.Swal !== "undefined");
        await page.evaluate(()=>Swal.close());
        await expect(page.getByText("Revisar costos",{exact:true})).toBeVisible();
        await capture("cotizacion-costo-desconocido");
        report.comprobaciones.push("Maestro crea preliminar con costo desconocido NULL; referencia cero no se toma como gratuidad");
        await page.goto(pedidoUrl);
        await expect(page.getByText('Ganancia sobre el costo:',{exact:false})).toHaveCount(0);
        expect((await context.request.get(base+'/admin/pedidos/create?cotizacion='+url.split('/').pop())).status()).toBe(403);
        await page.getByRole('link',{name:'Planificar materiales',exact:true}).click();
        await page.getByRole('link',{name:'Editar materiales',exact:true}).click();
        await page.locator('[data-plan-agregar]').first().click();
        const nueva=page.locator('[data-plan-fila]').filter({has:page.locator('select')}).last();
        await nueva.locator('select').selectOption(String(datos.secundario));
        await nueva.locator('[name$="[cantidad_planificada]"]').fill('2');
        await page.locator('#motivo').fill('Material adicional revisado por maestro');
        await page.getByRole('button',{name:'Actualizar materiales',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Planificación actualizada');
        await page.evaluate(()=>Swal.close());
        await expect(page.getByText('Añadido después',{exact:true})).toBeVisible();
        await page.goto(pedidoUrl);
        await page.getByRole('button',{name:'Iniciar fabricación',exact:true}).click();
        await page.getByRole('button',{name:'Sí, iniciar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Fabricación iniciada');
        await page.evaluate(()=>Swal.close());
        await expect(page.getByRole('button',{name:'Iniciar fabricación',exact:true})).toHaveCount(0);
        await page.getByRole('link',{name:'Registrar materiales utilizados',exact:true}).click();
        await expect(page).toHaveURL(pedidoUrl+'/fabricacion/edit');
        const usados = page.locator('[data-uso-cantidad]');
        await expect(usados).toHaveCount(3);
        await expect(usados.first()).toHaveValue('');
        await usados.first().fill('5.25');
        await expect(page.locator('[data-uso-copiar]')).toHaveCount(0);
        await expect(usados.first()).toHaveValue('5.25');
        for(let indice=1;indice<3;indice++) { await usados.nth(indice).fill(await usados.nth(indice).getAttribute('data-uso-plan')); }
        await expect(page.locator('[data-uso-diferencia]').first()).toHaveText('-0,75');
        await expect(page.locator('[name="recargo_porcentaje"], [name="precio_total_acordado"], [name="uso_registrado_por_id"]')).toHaveCount(0);
        await page.getByRole('button',{name:'Añadir material utilizado',exact:true}).first().click();
        const extraUso = page.locator('[data-uso-fila]').filter({has:page.locator('select')}).last();
        await extraUso.locator('select').selectOption(String(datos.pendiente));
        await extraUso.locator('[data-uso-cantidad]').fill('0.75');
        await expect(extraUso.locator('[data-uso-unidad]')).toHaveText('Metro');
        await expect(extraUso.locator('[data-uso-diferencia]')).toHaveText('+0,75');
        for(const width of [1440,1024,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Materiales utilizados '+width); await capture('fabricacion-edit-'+width); }
        await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();
        await noOverflow('Materiales utilizados oscuro móvil');
        await capture('fabricacion-edit-oscuro-390');
        await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();
        await page.getByRole('button',{name:'Guardar materiales',exact:true}).click();
        await expect(page).toHaveURL(pedidoUrl);
        await expect(page.locator('.swal2-popup')).toContainText('Materiales utilizados guardados');
        await page.evaluate(()=>Swal.close());
        const usoResumen = page.getByRole('region',{name:'Materiales utilizados del mueble 1',exact:true});
        await expect(usoResumen).toContainText('5,25');
        await expect(usoResumen).toContainText('0,75');
        await page.getByRole('link',{name:'Registrar materiales utilizados',exact:true}).click();
        await page.locator('[data-uso-cantidad]').first().fill('5');
        await expect(page.locator('[data-uso-motivo]')).toHaveAttribute('required','');
        const formularioViejo = await page.locator('[data-materiales-utilizados]').evaluate(el=>Object.fromEntries(new FormData(el)));
        await page.locator('[data-uso-motivo]').fill('Corrección de cantidad durante prueba aislada');
        await page.getByRole('button',{name:'Guardar materiales',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Materiales utilizados guardados');
        await page.evaluate(()=>Swal.close());
        const repetido = await context.request.post(pedidoUrl+'/fabricacion',{form:formularioViejo,headers:{Accept:'application/json'},maxRedirects:0});
        expect(repetido.status()).toBe(422);
        expect((await repetido.json()).errors).toHaveProperty('version');
        await expect(page.getByText('Bs 425,00',{exact:true}).first()).toBeVisible();
        await expect(page.getByRole('region',{name:'Materiales utilizados del mueble 1',exact:true})).toContainText('5');
        for(const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Consulta materiales utilizados '+width); await capture('fabricacion-show-'+width); }
        report.comprobaciones.push('4C: maestro registra totales, copia solo vacíos, añade material, corrige con motivo y bloquea reenvío obsoleto; precios y existencia conservados');
        await page.getByRole('button',{name:'Validar materiales',exact:true}).click();
        await page.getByRole('button',{name:'Cancelar',exact:true}).click();
        await expect(page.getByText('Materiales pendientes de validación',{exact:true})).toBeVisible();
        await page.getByRole('button',{name:'Validar materiales',exact:true}).click();
        await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Materiales revisados y validados');
        await page.evaluate(()=>Swal.close());
        await expect(page.getByText('Materiales validados',{exact:true})).toBeVisible();
        await expect(page.getByRole('button',{name:'Terminar fabricación',exact:true})).toHaveCount(0);
        const csrf4d=await page.locator('input[name="_token"]').first().inputValue();
        expect((await context.request.post(pedidoUrl+'/terminar',{form:{_token:csrf4d},headers:{Accept:'application/json'},maxRedirects:0})).status()).toBe(403);
        report.comprobaciones.push('4D: maestro cancela o confirma la validación con SweetAlert2 y no puede cerrar por interfaz ni petición directa');


        await expect(page.getByText('En fabricación',{exact:true}).first()).toBeVisible();
        for (const width of [1440,1024,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Pedido maestro '+width); await capture('pedido-maestro-'+width); }
        await page.goto(base+'/admin/existencias?material_id='+datos.material);
        await expect(page.getByText('1,5 Metro',{exact:true})).toBeVisible();
        report.comprobaciones.push('Maestro modifica planificación y añade material, inicia fabricación con SweetAlert2 y conserva existencias; sin aceptación ni ganancias');
        await page.goto(base+'/admin');
        await expect(page.getByRole('article',{name:'Pedidos en fabricación: 1',exact:true})).toBeVisible();
        await expect(page.getByRole('heading',{name:'Cotizaciones recientes',exact:true})).toBeVisible();
        await expect(page.getByRole('link',{name:'Ver pedido '+pedidoId,exact:true})).toHaveAttribute('href',pedidoUrl);
        await expect(page.getByText('Resumen administrativo',{exact:true})).toHaveCount(0);
        for(const width of [1440,1024,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Panel operativo maestro '+width); await capture('dashboard-maestro-'+width); }
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await login('propietario');
        for(const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Panel operativo propietario '+width); await capture('dashboard-propietario-'+width); }
        report.comprobaciones.push('Dashboard muestra conteos y enlaces del pedido creado, sin ganancias ni importes, responsive para ambos roles');
        await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();
        await expect(page.locator('html')).toHaveAttribute('data-theme','dark');
        await expect(page.getByRole('button',{name:'Activar tema claro',exact:true})).toHaveAttribute('aria-pressed','true');
        expect(await page.evaluate(()=>localStorage.getItem('tajibo.tema'))).toBe('dark');
        for(const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Panel oscuro '+width); await capture('tema-oscuro-dashboard-'+width); }
        await page.goto(base+'/admin/cotizaciones/create');
        await expect(page.locator('html')).toHaveAttribute('data-theme','dark');
        const largoTema = page.locator('[name="detalles[0][largo_cm]"]');
        await largoTema.fill('141');
        await page.getByRole('button',{name:'Activar tema claro',exact:true}).focus();
        await page.keyboard.press('Space');
        await expect(page.locator('html')).toHaveAttribute('data-theme','light');
        await expect(largoTema).toHaveValue('141');
        await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();
        await expect(largoTema).toHaveValue('141');
        expect(await largoTema.evaluate(el=>getComputedStyle(el).backgroundColor)).toBe('rgb(17, 28, 40)');
        for(const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Formulario oscuro '+width); await capture('tema-oscuro-cotizacion-'+width); }
        await page.reload();
        await expect(page.locator('html')).toHaveAttribute('data-theme','dark');
        await expect(page.getByRole('button',{name:'Activar tema claro',exact:true})).toBeVisible();
        await page.goto(base+'/admin/cotizaciones');
        await expect(page.locator('html')).toHaveAttribute('data-theme','dark');
        expect(await page.locator('.content-wrapper thead th').first().evaluate(el=>getComputedStyle(el).backgroundColor)).toBe('rgb(36, 55, 74)');
        await capture('tema-oscuro-cotizaciones-listado');
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();
        await expect(page.locator('html')).toHaveAttribute('data-theme','dark');
        await noOverflow('Acceso oscuro móvil');
        await capture('tema-oscuro-acceso');
        await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();
        await page.reload();
        await expect(page.locator('html')).toHaveAttribute('data-theme','light');
        report.comprobaciones.push('Tema claro/oscuro accesible por teclado, conserva datos sin recargar y persiste al navegar, recargar y cerrar sesión; formularios y tablas adaptados');

        await login('propietario');
        await page.goto(pedidoUrl);
        await expect(page.getByText('Materiales validados',{exact:true})).toBeVisible();
        await page.locator('label[for="confirmar-precio"]').click();
        await expect(page.locator('#confirmar-precio')).toBeChecked();
        const cierreForm=page.locator('form[action="'+pedidoUrl+'/terminar"]');
        const cierreDatos=await cierreForm.evaluate(el=>Object.fromEntries(new FormData(el)));
        for(const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Revisión final '+width); await capture('cierre-revision-'+width); }
        await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();
        await noOverflow('Revisión final oscuro móvil'); await capture('cierre-revision-oscuro-390');
        await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();
        await page.getByRole('button',{name:'Terminar fabricación',exact:true}).click();
        await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Fabricación terminada');
        await page.evaluate(()=>Swal.close());
        await expect(page.getByRole('button',{name:'Registrar entrega',exact:true})).toBeVisible();
        await expect(page.getByRole('link',{name:'Registrar materiales utilizados',exact:true})).toHaveCount(0);
        await expect(page.getByRole('link',{name:'Ver planificación',exact:true})).toBeVisible();
        await expect(page.getByRole('link',{name:'Editar',exact:true})).toHaveCount(0);
        await expect(page.getByText('Bs 425,00',{exact:true}).first()).toBeVisible();
        expect((await context.request.post(pedidoUrl+'/terminar',{form:cierreDatos,maxRedirects:0})).status()).toBe(302);
        expect((await context.request.get(pedidoUrl+'/fabricacion/edit')).status()).toBe(403);
        for(const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Pedido terminado '+width); await capture('pedido-terminado-'+width); }
        await page.locator('#observaciones-entrega').fill('Entrega de prueba en taller, sin operaciones de pago.');
        await expect(page.locator('[data-saldo-entrega]')).toContainText('425,00');
        const entregaDatos=await page.locator('form[action="'+pedidoUrl+'/entregar"]').evaluate(el=>Object.fromEntries(new FormData(el)));
        await page.getByRole('button',{name:'Registrar entrega',exact:true}).click();
        await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Entrega registrada correctamente');
        await page.evaluate(()=>Swal.close());
        await expect(page.getByRole('button',{name:'Registrar entrega',exact:true})).toHaveCount(0);
        await expect(page.getByText('Pedido entregado. Consulta la fecha y el responsable en Seguimiento.',{exact:true})).toBeVisible();
        await expect(page.getByText('Entrega de prueba en taller, sin operaciones de pago.',{exact:true})).toBeVisible();
        expect((await context.request.post(pedidoUrl+'/entregar',{form:entregaDatos,maxRedirects:0})).status()).toBe(302);
        for(const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Pedido entregado '+width); await capture('pedido-entregado-'+width); }
        await page.goto(base+'/admin/pedidos?estado=entregado');
        await expect(page.locator('.content-wrapper tbody')).toContainText('Entregado');
        await page.goto(base+'/admin/existencias?material_id='+datos.material);
        await expect(page.getByText('1,5 Metro',{exact:true})).toBeVisible();
        report.comprobaciones.push('4D: propietario confirma precio, termina y entrega; repetición idempotente, edición bloqueada, filtros y existencias conservadas');

        const prepararCasos=spawnSync(php,[path.join(__dirname,'preparar-casos.php')],{cwd:root,env,encoding:'utf8',windowsHide:true});
        if(prepararCasos.status!==0)throw new Error(prepararCasos.stderr||prepararCasos.stdout);
        const casosQA=JSON.parse(fs.readFileSync(path.join(dir,'casos5a.json'),'utf8'));
        expect(casosQA.simulados).toBe(35);expect(casosQA.materiales).toBe(172);
        await page.goto(base+'/admin/casos?origen=simulado');
        await expect(page.getByRole('heading',{name:'Casos históricos',exact:true})).toBeVisible();
        await expect(page.locator('.content-wrapper tbody')).toContainText('Registro inicial');
        for(const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Casos históricos '+width); await capture('casos-listado-'+width); }
        const casoUrl=base+'/admin/casos/'+casosQA.primero;
        await page.goto(casoUrl);
        await expect(page.getByRole('region',{name:'Materiales del caso',exact:true})).toContainText('Melamina');
        await expect(page.getByText('Costos y precio del grupo',{exact:true})).toBeVisible();
        await expect(page.getByText('Bs 577,65',{exact:true})).toBeVisible();
        for(const width of [1440,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Ficha caso '+width); for(const nombre of ['Validar materiales del caso','Validar precio del caso']) { const caja=await page.getByRole('button',{name:nombre,exact:true}).boundingBox(); expect(caja.x).toBeGreaterThanOrEqual(0); expect(caja.x+caja.width).toBeLessThanOrEqual(width); } await capture('caso-detalle-'+width); }
        await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();
        await noOverflow('Ficha caso oscuro móvil');await capture('caso-detalle-oscuro-390');
        await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();await login('maestro');await page.goto(casoUrl);
        await expect(page.getByRole('button',{name:'Validar precio del caso',exact:true})).toHaveCount(0);
        await page.getByRole('button',{name:'Validar materiales del caso',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Revisión registrada');await page.evaluate(()=>Swal.close());
        await expect(page.getByText('Registro inicial de casos históricos.',{exact:false})).toHaveCount(0);
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();await login('propietario');await page.goto(casoUrl);
        await page.getByRole('button',{name:'Validar precio del caso',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Revisión registrada');await page.evaluate(()=>Swal.close());
        await expect(page.getByText('Revisado',{exact:true}).first()).toBeVisible();
        await expect(page.getByText('Registro inicial de casos históricos.',{exact:false})).toHaveCount(0);
        await capture('caso-revisado');
        report.comprobaciones.push('5A: 35 casos/172 materiales; consulta de cantidades, costos y precio; revisión separada por roles con SweetAlert2, origen simulado conservado');

        const cotizacionSimilar=base+'/admin/cotizaciones/'+casosQA.cotizacion_similitud;
        const consultaSimilar=cotizacionSimilar+'/detalles/'+casosQA.detalle_similitud+'/similares';
        await page.goto(cotizacionSimilar);
        await page.getByRole('link',{name:'Buscar casos similares',exact:true}).first().click();
        await expect(page).toHaveURL(consultaSimilar);
        await expect(page.getByRole('link',{name:'Ver demostración experimental',exact:true})).toHaveCount(0);
        const referencias=page.locator('section[aria-label^="Caso similar "]');
        await expect(referencias).toHaveCount(2);
        await expect(referencias.nth(0)).toContainText('96,63 % de similitud');
        await expect(referencias.nth(1)).toContainText('88,63 % de similitud');
        await referencias.first().getByText('Ver características y diferencias',{exact:true}).click();
        await expect(referencias.first().getByRole('region',{name:/Diferencias con el caso/})).toContainText('Solicitado');
        await expect(page.getByRole('button',{name:'Aplicar referencia',exact:true})).toHaveCount(0);
        for(const width of [1440,1024,390]) { await page.setViewportSize({width,height:1000}); await noOverflow('Casos similares '+width); expect(await referencias.first().getByRole('region',{name:/Diferencias con el caso/}).evaluate(el=>el.scrollWidth<=el.clientWidth+1)).toBe(true); await capture('similitud-resultados-'+width); }
        await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();
        await noOverflow('Casos similares oscuro móvil');await capture('similitud-oscuro-390');
        await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();
        expect((await context.request.get(consultaSimilar+'?umbral=0',{headers:{Accept:'application/json'}})).status()).toBe(422);
        await page.goto(cotizacionSimilar+'/detalles/'+casosQA.detalle_fuera_cobertura+'/similares');
        await expect(page.getByText('Las características están fuera de los rangos cubiertos',{exact:false})).toBeVisible();
        await expect(page.getByText('Fuera de cobertura: Largo (cm).',{exact:true})).toBeVisible();
        await expect(referencias).toHaveCount(0);await capture('similitud-sin-cobertura-390');
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();await login('maestro');
        await page.goto(consultaSimilar+'');
        await expect(referencias).toHaveCount(2);
        await expect(page.locator('[name="recargo_porcentaje"], [name="precio_final"]')).toHaveCount(0);
        await capture('similitud-maestro-390');
        report.comprobaciones.push('Búsqueda única de casos históricos desde detalle guardado: dos vecinos 96,63/88,63, cobertura estricta, parámetros manipulados rechazados y acceso maestro sin aprobación');
        await page.goto(base+'/admin/cotizaciones/create');
        const nuevoMueble = page.locator('[data-detalle]').first();
        expect(await nuevoMueble.locator('[data-llenado-costos]').evaluate(el=>el.open)).toBe(false);
        const caracteristica = nombre => nuevoMueble.locator('[name$="['+nombre+']"]');
        await caracteristica('tipo_mueble_id').selectOption(String(datos.mesa));
        await caracteristica('material_principal_id').selectOption({label:'Madera'});
        for(const [nombre,valor] of Object.entries({cantidad_muebles:'2',largo_cm:'150',ancho_cm:'85',alto_cm:'76'})) await caracteristica(nombre).fill(valor);
        await caracteristica('acabado').selectOption('Barnizado natural');
        await expect(nuevoMueble.locator('[data-cantidad-unitaria]')).toHaveValue('');
        await nuevoMueble.getByRole('button',{name:'Buscar casos similares',exact:true}).click();
        await expect(nuevoMueble.getByRole('button',{name:'Buscar casos similares',exact:true})).toHaveCount(1);
        const propuestasFormulario = nuevoMueble.locator('[data-resultados-casos] [aria-label^="Caso similar "]');
        await expect(propuestasFormulario).toHaveCount(2);
        await expect(propuestasFormulario.first()).toContainText('Recomendación principal');
        await expect(propuestasFormulario.first()).toContainText('96,63 % de similitud');
        await expect(propuestasFormulario.nth(1)).toContainText('88,63 % de similitud');
        await expect(propuestasFormulario.first().locator('[aria-label="Comparación económica de la propuesta"] dd')).toHaveCount(2);
        await expect(propuestasFormulario.first()).toContainText('Comparación para la misma cantidad solicitada');
        await expect(propuestasFormulario.first().locator('[data-aviso-lote]')).toHaveCount(0);
        await expect(propuestasFormulario.nth(1).locator('[data-aviso-lote]')).toContainText('ahorros por trabajo conjunto');
        report.comprobaciones.push('5C: dos estimaciones para la misma cantidad y aviso de cambio de lote; costos/antecedentes originales conservados');
        await nuevoMueble.locator('[data-llenado-costos]').evaluate(el=>el.open=false);
        for(const width of [1440,1024,390]) {
            await page.setViewportSize({width,height:1000});
            await noOverflow('Búsqueda en formulario '+width);
            await capture('formulario-similitud-'+width);
        }
        await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();
        await noOverflow('Búsqueda en formulario oscuro 390');await capture('formulario-similitud-oscuro-390');
        await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();
        await nuevoMueble.getByRole('button',{name:'Usar propuesta principal',exact:true}).click();
        await page.locator('.swal2-cancel').click();
        await expect(nuevoMueble.locator('[data-cantidad-unitaria]')).toHaveValue('');
        await nuevoMueble.getByRole('button',{name:'Usar propuesta principal',exact:true}).click();
        await page.getByRole('button',{name:'Sí, usar propuesta',exact:true}).click();
        await expect(nuevoMueble.locator('[data-material-row]')).toHaveCount(5);
        await expect(nuevoMueble.locator('[data-cantidad-unitaria]').first()).toHaveValue('8');
        await expect(nuevoMueble.locator('[data-mano-unitaria]')).toHaveValue('250');
        await expect(nuevoMueble.locator('[data-otros-costos]')).toHaveValue('30');
        const referenciaPrincipal = await nuevoMueble.locator('[data-referencia-caso]').inputValue();
        expect(referenciaPrincipal.length>100).toBe(true);
        await nuevoMueble.locator('[data-mano-unitaria]').fill('260.00');
        await expect(nuevoMueble.locator('[data-mano-unitaria]')).toHaveValue('260.00');
        await capture('formulario-propuesta-editable-390');
        await nuevoMueble.getByRole('button',{name:'Usar este caso',exact:true}).click();
        await page.getByRole('button',{name:'Sí, usar propuesta',exact:true}).click();
        await expect(nuevoMueble.locator('[data-mano-unitaria]')).toHaveValue('310');
        await expect(nuevoMueble.locator('[data-cantidad-unitaria]').first()).toHaveValue('9');
        expect(await nuevoMueble.locator('[data-referencia-caso]').inputValue()).not.toBe(referenciaPrincipal);
        await caracteristica('largo_cm').fill('151');
        await expect(propuestasFormulario).toHaveCount(0);
        await expect(nuevoMueble.locator('[data-estado-busqueda]')).toContainText('Las características cambiaron');
        await expect(nuevoMueble.locator('[data-propuesta-aplicada]')).toContainText('Revisa materiales y costos');
        await caracteristica('largo_cm').fill('150');
        await nuevoMueble.getByRole('button',{name:'Buscar casos similares',exact:true}).click();
        await expect(propuestasFormulario).toHaveCount(2);
        // El guardado normal acepta los importes editables, conserva el origen y no aprueba.
        await nuevoMueble.locator('[data-mano-unitaria]').fill('315.00');
        await page.getByRole('button',{name:'Registrar cotización',exact:true}).click();
        await expect(page).toHaveURL(/\/admin\/cotizaciones\/\d+$/);
        await page.waitForFunction(()=>window.Swal);await page.evaluate(()=>Swal.close());
        await expect(page.getByText('Borrador',{exact:true}).first()).toBeVisible();
        await capture('cotizacion-desde-propuesta');
        report.comprobaciones.push('Formulario: buscar sin guardar/materiales/costos, principal=mejor vecino, alternativas, confirmación/cancelación, precios actuales, edición, invalidación por cambio y guardado sin aprobar');
        // Pagos: todo este recorrido usa exclusivamente la SQLite temporal de la prueba.
        await expect(page.locator('#sidebar-menu').getByRole('link',{name:'Pagos',exact:true})).toHaveCount(0);
        expect((await context.request.get(base+'/admin/pagos',{headers:{Accept:'application/json'}})).status()).toBe(403);
        await page.goto(pedidoUrl);
        await expect(page.getByText('Pagos del pedido',{exact:true})).toHaveCount(0);
        await expect(page.getByText('Saldo según registros',{exact:true})).toHaveCount(0);
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();await login('propietario');
        await page.goto(pedidoUrl);
        await page.getByRole('link',{name:'Ver pagos',exact:true}).click();
        await expect(page.locator('[data-pago-saldo]')).toHaveText('Bs 425,00');
        await page.getByRole('link',{name:'Nuevo pago',exact:true}).click();
        await expect(page.locator('#pedido_id')).toHaveValue(String(pedidoId));
        expect(await page.locator('#medio option').allTextContents()).toEqual(['Selecciona un medio','Efectivo','QR']);
        for(const width of [1440,1024,390]) {await page.setViewportSize({width,height:1000});await noOverflow('Registrar pago '+width);await capture('pagos-create-'+width);}
        await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();await noOverflow('Registrar pago oscuro 390');await capture('pagos-create-oscuro-390');
        await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();
        await page.locator('#importe').fill('100.00');await page.locator('#medio').selectOption('qr');await page.locator('#referencia').fill('QR recibido en taller');
        const altaPago=await page.locator('form[data-cierre-pedido]').evaluate(el=>Object.fromEntries(new FormData(el)));
        await page.getByRole('button',{name:'Registrar pago',exact:true}).click();await page.locator('.swal2-cancel').click();
        await expect(page.locator('#importe')).toHaveValue('100.00');
        await page.getByRole('button',{name:'Registrar pago',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page).toHaveURL(/\/admin\/pagos\/\d+$/);const pagoUrl=page.url();
        await expect(page.locator('.swal2-popup')).toContainText('Pago registrado');await page.evaluate(()=>Swal.close());
        await expect(page.locator('[data-pago-pagado]')).toHaveText('Bs 100,00');await expect(page.locator('[data-pago-saldo]')).toHaveText('Bs 325,00');
        expect((await context.request.post(base+'/admin/pagos/create',{form:altaPago,maxRedirects:0})).status()).toBe(302);
        await page.goto(base+'/admin/pagos?pedido_id='+pedidoId);await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(1);
        await page.goto(pagoUrl+'/edit');await page.locator('#importe').fill('125.00');await page.locator('#medio').selectOption('efectivo');await page.locator('#motivo').fill('Corrección del importe efectivamente recibido');
        const edicionPago=await page.locator('form[data-cierre-pedido]').evaluate(el=>Object.fromEntries(new FormData(el)));
        await noOverflow('Editar pago 390');await capture('pagos-edit-390');
        await page.getByRole('button',{name:'Actualizar pago',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Pago actualizado');await page.evaluate(()=>Swal.close());
        await expect(page.locator('[data-pago-saldo]')).toHaveText('Bs 300,00');
        await expect(page.getByText('Responsables del registro',{exact:true})).toBeVisible();
        for(const width of [1440,1024,390]) {await page.setViewportSize({width,height:1000});await noOverflow('Ver pago '+width);await capture('pagos-show-'+width);}
        expect((await context.request.post(pagoUrl,{form:edicionPago,headers:{Accept:'application/json'},maxRedirects:0})).status()).toBe(422);
        await page.goto(base+'/admin/pagos/create?pedido_id='+pedidoId);await page.locator('#importe').fill('301.00');await page.locator('#medio').selectOption('qr');
        await page.getByRole('button',{name:'Registrar pago',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page.getByText(/El importe supera el saldo disponible/).first()).toBeVisible();await expect(page.locator('#importe')).toHaveValue('301.00');
        await page.locator('#importe').fill('300.00');await page.getByRole('button',{name:'Registrar pago',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Pago registrado');await page.evaluate(()=>Swal.close());
        await expect(page.locator('[data-pago-saldo]')).toHaveText('Bs 0,00');await expect(page.getByText('Pagado',{exact:true})).toBeVisible();
        await page.goto(pagoUrl+'/edit');await page.locator('#importe').fill('124.00');await page.locator('#motivo').fill('Corrección posterior conservando entrega');
        await page.getByRole('button',{name:'Actualizar pago',exact:true}).click();await page.getByRole('button',{name:'Sí, confirmar',exact:true}).click();
        await expect(page.locator('.swal2-popup')).toContainText('Pago actualizado');await page.evaluate(()=>Swal.close());
        await expect(page.locator('[data-pago-saldo]')).toHaveText('Bs 1,00');
        await page.goto(pedidoUrl);await expect(page.getByText('Pedido entregado. Consulta la fecha y el responsable en Seguimiento.',{exact:true})).toBeVisible();
        await expect(page.locator('[data-pago-saldo]')).toHaveText('Bs 1,00');await capture('pagos-pedido-entregado');
        report.comprobaciones.push('6A: pagos efectivo/QR, cancelación, duplicados, edición auditada, versión obsoleta, sobrepago, saldo cero y corrección posterior sin reabrir entrega; maestro sin datos ni acceso');
        // Reportes: consultas y PDF sobre la misma base temporal, sin tocar MySQL.
        await page.goto(base+'/admin/reportes');
        await expect(page.getByRole('link',{name:'Consultar cotizaciones',exact:true})).toBeVisible();
        await expect(page.getByRole('link',{name:'Consultar pedidos',exact:true})).toBeVisible();
        await expect(page.getByRole('link',{name:'Consultar pagos recibidos',exact:true})).toBeVisible();
        await expect(page.getByRole('link',{name:'Consultar saldos actuales',exact:true})).toBeVisible();
        for(const width of [1440,1024,390]) {await page.setViewportSize({width,height:1000});await noOverflow('Reportes '+width);await capture('reportes-index-'+width);}
        for(const tipo of ['cotizaciones','pedidos','pagos','saldos']) {
            await page.goto(base+'/admin/reportes/'+tipo+(tipo==='saldos'?'':'?desde=2026-01-01&hasta=2026-12-31'));
            await expect(page.getByRole('region',{name:'Detalle del reporte',exact:true})).toBeVisible();
            for(const width of [1440,1024,390]) {await page.setViewportSize({width,height:1000});await noOverflow('Reporte '+tipo+' '+width);await capture('reporte-'+tipo+'-'+width);}
            const enlacePdf=await page.getByRole('link',{name:'PDF imprimible',exact:true}).getAttribute('href');
            const pdf=await context.request.get(enlacePdf);
            expect(pdf.status()).toBe(200);expect(pdf.headers()['content-type']).toContain('application/pdf');
            expect((await pdf.body()).subarray(0,5).toString()).toBe('%PDF-');
            if(tipo==='pagos') {
                await expect(page.locator('[data-reporte-total]').last()).toHaveText('424,00');
                await page.locator('#medio').selectOption('qr');await page.getByRole('button',{name:'Consultar',exact:true}).click();
                await expect(page.locator('[data-reporte-total]').last()).toHaveText('300,00');
                await expect(page.locator('.content-wrapper tbody tr')).toHaveCount(1);
            }
            if(tipo==='saldos') {
                await expect(page.locator('[data-reporte-total]').last()).toHaveText('1,00');
                await expect(page.locator('#desde')).toHaveCount(0);
                await page.getByRole('button',{name:'Activar tema oscuro',exact:true}).click();await capture('reporte-saldos-oscuro-390');
                await page.getByRole('button',{name:'Activar tema claro',exact:true}).click();
            }
        }
        await page.goto(base+'/admin/reportes/pagos?desde=2020-01-01&hasta=2020-01-31');
        await expect(page.getByText('No hay registros para estos filtros.',{exact:true})).toBeVisible();
        await expect(page.locator('[data-reporte-total]').last()).toHaveText('0,00');
        expect((await context.request.get(base+'/admin/reportes/saldos?desde=2026-01-01',{headers:{Accept:'application/json'}})).status()).toBe(422);
        await abrirOpcionesCuenta(page);
        await page.getByRole('button',{name:'Salir',exact:true}).click();await login('maestro');
        await expect(page.locator('#sidebar-menu').getByRole('link',{name:'Reportes',exact:true})).toHaveCount(0);
        for(const url of ['/admin/reportes','/admin/reportes/pagos','/admin/reportes/saldos/pdf']) {
            expect((await context.request.get(base+url,{headers:{Accept:'application/json'}})).status()).toBe(403);
        }
        report.comprobaciones.push('6B: cuatro reportes, PDF con filtros, efectivo/QR, saldo actual, período sin datos, validación, propietario y maestro bloqueado; escritorio/tableta/móvil y oscuro');
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
