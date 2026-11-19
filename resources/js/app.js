document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-password-toggle]");
    if (!button) return;
    const input = document.getElementById(button.dataset.passwordToggle);
    const visible = input.type === "password";
    input.type = visible ? "text" : "password";
    button.setAttribute("aria-pressed", String(visible));
    button.setAttribute(
        "aria-label",
        visible ? "Ocultar contraseña" : "Mostrar contraseña",
    );
});
document.addEventListener("keydown", (event) => {
    if (
        event.key === "Escape" &&
        document.body.classList.contains("sidebar-open")
    ) {
        document.querySelector(".sidebar-close")?.click();
    }
});
document.addEventListener("click", (event) => {
    const trigger = event.target.closest('[data-widget="pushmenu"]');
    if (!trigger) return;
    setTimeout(() => {
        const open = document.body.classList.contains("sidebar-open");
        document
            .querySelector('.main-header [data-widget="pushmenu"]')
            ?.setAttribute("aria-expanded", String(open));
        if (trigger.classList.contains("sidebar-close"))
            document
                .querySelector('.main-header [data-widget="pushmenu"]')
                ?.focus();
    }, 0);
});


import './cotizaciones';

import './pedidos';

import './tema';

