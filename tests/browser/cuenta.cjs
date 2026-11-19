const { expect } = require('@playwright/test');

async function abrirOpcionesCuenta(page) {
    const menuLateralAbierto = await page.evaluate(() => innerWidth < 992 && document.body.classList.contains('sidebar-open'));
    if (menuLateralAbierto) await page.getByRole('button', {name:'Cerrar menú', exact:true}).click();
    const menu = page.locator('#header-account-menu');
    if (!await menu.isVisible()) await page.getByRole('button', {name:'Opciones de mi cuenta', exact:true}).click();
    await expect(menu).toBeVisible();
}

module.exports = { abrirOpcionesCuenta };
