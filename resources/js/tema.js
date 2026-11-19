// Preferencia visual de este navegador; no altera los datos del usuario.
function aplicarTema(tema) {
    const oscuro = tema === 'dark';
    document.documentElement.dataset.theme = oscuro ? 'dark' : 'light';
    document.documentElement.style.colorScheme = oscuro ? 'dark' : 'light';
    document.querySelectorAll('[data-theme-toggle]').forEach((boton) => {
        const accion = oscuro ? 'Activar tema claro' : 'Activar tema oscuro';
        boton.setAttribute('aria-pressed', String(oscuro));
        boton.setAttribute('aria-label', accion);
        boton.title = accion;
        boton.querySelector('[data-theme-icon]').className = oscuro ? 'fas fa-sun' : 'fas fa-moon';
        boton.querySelector('[data-theme-label]').textContent = oscuro ? 'Tema claro' : 'Tema oscuro';
    });
}

function iniciarTema() {
    aplicarTema(document.documentElement.dataset.theme);
    document.querySelectorAll('[data-theme-toggle]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const tema = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            aplicarTema(tema);
            try { localStorage.setItem('tajibo.tema', tema); } catch {}
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarTema, { once: true });
} else {
    iniciarTema();
}

window.addEventListener('storage', (evento) => {
    if (evento.key === 'tajibo.tema' || evento.key === null) {
        aplicarTema(evento.key === 'tajibo.tema' ? evento.newValue : 'light');
    }
});
