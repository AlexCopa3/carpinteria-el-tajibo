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
    const ubicacion=JSON.parse(fs.readFileSync(path.join(root,'storage/app/private/'+(process.env.TAJIBO_HISTORIAL_UBICACION||'casos-87-historial-actual.json')),'utf8'));
    const ensayo=JSON.parse(fs.readFileSync(path.join(ubicacion.dir,'ensayo.json'),'utf8'));
    if(!/^carpinteria_el_tajibo_ensayo_datos_[0-9]{14}_[a-f0-9]{6}$/.test(ensayo.base_ensayo)) throw new Error('Base QA no autorizada');
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
        DB_CONNECTION: "mysql",
        DB_DATABASE: ensayo.base_ensayo,
        DB_URL: "",
        CACHE_STORE: "array",
        SESSION_DRIVER: "database",
        SESSION_COOKIE: `tajibo_qa_${port}`,
        MAIL_MAILER: "array",
        AUTH_PASSWORD_RESET_ENABLED: "false",
        QUEUE_CONNECTION: "sync",
        TAJIBO_QA_DIR: dir,
        APP_CONFIG_CACHE: path.relative(root, path.join(dir, "config.php")).replace(/\\/g, "/"),
        BCRYPT_ROUNDS: "4",
    };
    const php = process.env.TAJIBO_PHP || "C:/wamp64/bin/php/php8.3.28/php.exe";
    const preparation = spawnSync(php, [path.join(__dirname, "preparar-casos-pedidos.php")], {
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
        baseDatos: "MySQL clon aislado",
        motor: "Microsoft Edge / Playwright",
        comprobaciones: [],
        capturas: [],
    };
    const output = path.join(root, process.env.TAJIBO_CAPTURAS || "docs/capturas/historial-87");
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
        const datos=JSON.parse(fs.readFileSync(path.join(dir,'datos-historial.json'),'utf8'));
        await login('propietario');
        await page.goto(base+'/admin/pedidos');
        const idsPedidos=await page.locator('tbody tr td:first-child').allTextContents();
        const numeros=idsPedidos.map(s=>Number(s.trim())).filter(n=>n>0);
        expect(numeros.length).toBeGreaterThan(0);
        expect(numeros).toEqual([...numeros].sort((a,b)=>b-a));
        await expect(page.locator('tbody tr').first()).toContainText('19/09/2026');
        await page.goto(base+'/admin/pedidos/'+credentials.pedido_antiguo);
        await expect(page.locator('body')).toContainText('18/03/2020');
        await capture('pedido-antiguo');
        report.comprobaciones.push('Pedidos: ID descendente, primer trabajo en 2020 y últimos trabajos en 2026');
        await page.goto(base+'/admin/casos');
        await expect(page.getByRole('heading',{name:'Casos históricos',exact:true})).toBeVisible();
        await expect(page.getByText('Total de casos históricos: 87')).toBeVisible();
        await expect(page.locator('tbody tr').first().locator('td').first()).toHaveText('87');
        await expect(page.locator('.pagination').getByRole('link',{name:'8',exact:true})).toBeVisible();
        await page.locator('.pagination').getByRole('link',{name:'8',exact:true}).click();
        await expect(page.locator('tbody tr')).toHaveCount(3);
        expect((await page.locator('tbody tr td:first-child').allTextContents()).map(v=>Number(v.trim()))).toEqual([3,2,1]);
        report.comprobaciones.push('87 casos con IDs del 1 al 87, ocho páginas y tres registros en la última');
        await page.goto(base+'/admin/casos');
        await capture('casos-listado');await noOverflow('Listado escritorio');
        for(const grupo of datos){
            await page.goto(base+'/admin/pedidos/'+grupo.pedido_id);
            await expect(page.getByRole('heading',{name:'Detalle del pedido',exact:true})).toBeVisible();
            await expect(page.locator('body')).toContainText('Pedido '+grupo.pedido_id);
            await capture('pedido-'+grupo.cantidad+'-casos');await noOverflow('Pedido '+grupo.cantidad+' casos');
            await page.goto(base+'/admin/casos/'+grupo.caso_id);
            await expect(page.getByRole('heading',{name:'Detalle del caso',exact:true})).toBeVisible();
            await expect(page.getByRole('link',{name:/Ver pedido/})).toHaveAttribute('href',base+'/admin/pedidos/'+grupo.pedido_id);
            report.comprobaciones.push('Pedido '+grupo.pedido_id+': '+grupo.cantidad+' casos, ficha y enlace al pedido correctos');
        }
        await capture('caso-desde-pedido');
        await expect(page.locator('body')).not.toContainText('Registro inicial de casos históricos');
        await page.goto(base+'/admin/clientes?q='+encodeURIComponent('Lucía Quispe Flores'));
        await expect(page.locator('tbody')).not.toContainText('@');
        await expect(page.locator('body')).toContainText('+591');
        await capture('clientes');
        await page.goto(base+'/admin/pagos');await expect(page.getByRole('heading',{name:'Pagos',exact:true})).toBeVisible();await capture('pagos');
        const pdf=await context.request.get(base+'/admin/reportes/pedidos/pdf');
        expect(pdf.status()).toBe(200);expect((await pdf.body()).subarray(0,5).toString()).toBe('%PDF-');
        report.comprobaciones.push('Contactos bolivianos, pagos y PDF del historial correctos');
        const consultas=JSON.parse(fs.readFileSync(path.join(dir,'consultas-historial.json'),'utf8'));
        for(const consulta of consultas){
            await page.goto(base+'/admin/cotizaciones/create');
            const detalle=page.locator('[data-detalle]').first();
            await detalle.locator('details').evaluateAll(els=>els.forEach(el=>el.open=true));
            for(const [campo,valor] of Object.entries(consulta.mueble)){
                const control=detalle.locator('[name$="['+campo+']"]');
                if(await control.evaluate(el=>el.tagName==='SELECT'))await control.selectOption(String(valor));
                else await control.fill(String(valor));
            }
            const respuesta=page.waitForResponse(r=>r.url().endsWith('/admin/cotizaciones/similares')&&r.request().method()==='POST');
            await detalle.locator('[data-buscar-casos]').click();
            const r=await respuesta;expect(r.status()).toBe(200);const datos=await r.json();
            if(datos.estado!=='encontrados')fs.writeFileSync(path.join(output,'consulta-error.json'),JSON.stringify({tipo:consulta.tipo,solicitado:consulta.mueble,enviado:r.request().postDataJSON(),respuesta:datos},null,2));expect(datos.estado).toBe('encontrados');expect(datos.cantidad).toBeGreaterThan(0);expect(datos.cantidad).toBeLessThanOrEqual(3);
            await expect(detalle.locator('[data-resultados-casos]')).toContainText(consulta.tipo);
            if(process.env.TAJIBO_REVISAR_CONSUMOS && consulta.tipo==='Mesa'){
                expect(datos.propuestas.map(p=>p.caso_id)).toEqual([86,82,62]);
                for(const p of datos.propuestas){
                    expect(Number(p.materiales.find(m=>m.nombre==='Madera').cantidad_unitaria)).toBe(4);
                    expect(Number(p.materiales.find(m=>m.nombre==='Tornillo para madera').cantidad_unitaria)).toBe(12);
                    expect(Number(p.materiales.find(m=>m.nombre==='Barniz').cantidad_unitaria)).toBeGreaterThan(0);
                    expect(Number(p.materiales.find(m=>m.nombre==='Sellador para madera').cantidad_unitaria)).toBeGreaterThan(0);
                }
                const panel=detalle.locator('[data-resultados-casos]');
                await panel.locator('.similar-case-details').filter({hasText:'Materiales y costos del caso'}).evaluateAll(els=>els.forEach(el=>el.open=true));
                await panel.screenshot({path:path.join(output,'mesas-consumos-corregidos.png'),style:'.main-header { visibility: hidden !important; }'});
                await panel.locator('.similar-case-details').filter({hasText:'Materiales y costos del caso'}).evaluateAll(els=>els.forEach(el=>el.open=false));
                report.comprobaciones.push('Mesas 86, 82 y 62: cuatro tablones, doce tornillos por mueble y consumibles de barnizado presentes');
            }

            if(process.env.TAJIBO_REVISAR_PROPUESTAS){
                const panel=detalle.locator('[data-resultados-casos]');
                const tarjetas=panel.locator('.similar-case');
                await expect(tarjetas).toHaveCount(datos.cantidad);
                const principal=tarjetas.first();
                await expect(principal).toHaveClass(/is-principal/);
                await principal.locator('summary').filter({hasText:'Materiales y costos del caso'}).click();
                await expect(principal.locator('.similar-case-materials li').first()).toBeVisible();
                await principal.getByText('Ver características y diferencias',{exact:true}).click();
                await expect(principal.locator('.similar-case-table')).toBeVisible();
                await principal.locator('summary').filter({hasText:'Materiales y costos del caso'}).click();
                await principal.getByText('Ver características y diferencias',{exact:true}).click();
                await panel.scrollIntoViewIfNeeded();
                const nombre=consulta.tipo.normalize('NFD').replace(/[\u0300-\u036f]/g,'');
                await panel.screenshot({path:path.join(output,'tarjetas-'+nombre+'.png'),style:'.main-header { visibility: hidden !important; }'});
                await principal.getByRole('button',{name:'Usar propuesta principal',exact:true}).click();
                await page.locator('.swal2-confirm').click();
                await expect(principal).toHaveClass(/is-selected/);
                await expect(principal.locator('[data-propuesta-seleccionada]')).toBeVisible();
                expect(await detalle.locator('[data-material-row]').count()).toBeGreaterThan(0);
                if(await tarjetas.count()>1 && await tarjetas.nth(1).locator('[data-usar-caso]').isEnabled()){
                    await tarjetas.nth(1).locator('[data-usar-caso]').click();
                    await page.locator('.swal2-confirm').click();
                    await expect(tarjetas.nth(1)).toHaveClass(/is-selected/);
                    await expect(principal).not.toHaveClass(/is-selected/);
                }
                await page.evaluate(()=>document.documentElement.dataset.theme='dark');
                await panel.screenshot({path:path.join(output,'tarjetas-'+nombre+'-oscuro.png'),style:'.main-header { visibility: hidden !important; }'});
                await page.evaluate(()=>document.documentElement.dataset.theme='light');
                await page.setViewportSize({width:390,height:844});
                await panel.scrollIntoViewIfNeeded();
                await noOverflow('Propuestas móvil '+consulta.tipo);
                await panel.screenshot({path:path.join(output,'tarjetas-'+nombre+'-movil.png'),style:'.main-header { visibility: hidden !important; }'});
                await page.setViewportSize({width:1440,height:1000});
                report.comprobaciones.push(consulta.tipo+': detalles desplegables, aplicación editable, selección de alternativa y temas claro/oscuro correctos');
            }

            await capture('similares-'+consulta.tipo.normalize('NFD').replace(/[\u0300-\u036f]/g,''));
            await noOverflow('Búsqueda '+consulta.tipo);
            report.comprobaciones.push(consulta.tipo+': formulario, búsqueda y propuestas disponibles');
        }
        await page.setViewportSize({width:390,height:844});await page.goto(base+'/admin/casos');await noOverflow('Casos móvil');await capture('casos-movil');
        if(errors.length)throw new Error(errors.join('\n'));
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
            "datos-historial.json",
            "consultas-historial.json",
            "config.php",
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
