// Formularios sencillos de pedidos y planificación.
const altaPedido = document.querySelector('[data-crear-pedido]');
if (altaPedido) {
    const selector = altaPedido.querySelector('[data-cliente-existente]');
    const campos = altaPedido.querySelector('[data-cliente-nuevo]');
    const mostrar = () => { campos.disabled = !!selector.value; campos.hidden = !!selector.value; };
    selector.addEventListener('change', mostrar);
    mostrar();
}
const plan = document.querySelector('[data-planificacion]');
if (plan) {
    let indice = [...plan.querySelectorAll('[data-plan-fila]')].reduce((max,fila) => {
        const nombre = fila.querySelector('[name]').name;
        return Math.max(max, Number(nombre.match(/\[(\d+)\]/)[1])+1);
    }, 0);
    plan.addEventListener('click', (event) => {
        const boton = event.target.closest('button');
        if (!boton) return;
        if (boton.matches('[data-plan-agregar]')) {
            const detalle = boton.closest('[data-plan-detalle]');
            detalle.querySelector('[data-plan-filas]').insertAdjacentHTML('beforeend',document.querySelector('#plan-fila-nueva').innerHTML.replaceAll('__I__',String(indice++)).replaceAll('__D__',detalle.dataset.planDetalle));
            detalle.querySelector('[data-plan-fila]:last-child select')?.focus();
        }
        if (boton.matches('[data-plan-quitar]')) boton.closest('[data-plan-fila]').remove();
    });
}
document.querySelector('[data-iniciar-pedido]')?.addEventListener('submit', async (event) => {
    const form = event.currentTarget;
    if (form.dataset.confirmado === '1' || !window.Swal) return;
    event.preventDefault();
    const resultado = await window.Swal.fire({icon:'question',title:'¿Iniciar fabricación?',text:'El pedido pasará a En fabricación. Las existencias no se descontarán.',showCancelButton:true,confirmButtonText:'Sí, iniciar',cancelButtonText:'Cancelar'});
    if (resultado.isConfirmed) { form.dataset.confirmado='1'; form.requestSubmit(); }
});


// Uso de materiales: vista previa exacta en diezmilésimas; el servidor recalcula y valida.
const usoMateriales = document.querySelector('[data-materiales-utilizados]');
if (usoMateriales) {
    let indice = [...usoMateriales.querySelectorAll('[data-uso-fila]')].reduce((max, fila) => {
        const nombre = fila.querySelector('[name]').name;
        return Math.max(max, Number(nombre.match(/\[(\d+)\]/)[1]) + 1);
    }, 0);
    const decimal = (texto) => {
        if (!/^\d{1,14}(?:\.\d{1,4})?$/.test(texto)) return null;
        const [entero, fraccion = ''] = texto.split('.');
        return BigInt(entero) * 10000n + BigInt(fraccion.padEnd(4, '0'));
    };
    const mostrar = (valor) => {
        const negativo = valor < 0n;
        const absoluto = negativo ? -valor : valor;
        const fraccion = String(absoluto % 10000n).padStart(4, '0').replace(/0+$/, '');
        return (negativo ? '-' : valor > 0n ? '+' : '') + String(absoluto / 10000n) + (fraccion ? ',' + fraccion : '');
    };
    const actualizar = () => {
        let correccion = false;
        usoMateriales.querySelectorAll('[data-uso-cantidad]').forEach((campo) => {
            const actual = decimal(campo.value.trim());
            const planeado = decimal(campo.dataset.usoPlan);
            const anterior = campo.dataset.usoAnterior;
            campo.closest('[data-uso-fila]').querySelector('[data-uso-diferencia]').textContent = actual === null || planeado === null ? '—' : mostrar(actual - planeado);
            if (anterior !== '' && (actual === null || decimal(anterior) !== actual)) correccion = true;
        });
        const motivo = usoMateriales.querySelector('[data-uso-motivo]');
        motivo.required = correccion;
        motivo.minLength = correccion ? 5 : 0;
        usoMateriales.querySelector('[data-uso-motivo-marca]').hidden = !correccion;
    };
    usoMateriales.addEventListener('input', actualizar);
    usoMateriales.addEventListener('change', (event) => {
        if (event.target.matches('[data-uso-material]')) {
            event.target.closest('[data-uso-fila]').querySelector('[data-uso-unidad]').textContent = event.target.selectedOptions[0]?.dataset.unidad || 'Unidad automática';
        }
    });
    usoMateriales.addEventListener('click', (event) => {
        const boton = event.target.closest('button');
        if (!boton) return;
        if (boton.matches('[data-uso-agregar]')) {
            const detalle = boton.closest('[data-uso-detalle]');
            detalle.querySelector('[data-uso-filas]').insertAdjacentHTML('beforeend', document.querySelector('#uso-fila-nueva').innerHTML.replaceAll('__I__', String(indice++)).replaceAll('__D__', detalle.dataset.usoDetalle));
            detalle.querySelector('[data-uso-fila]:last-child select')?.focus();
        }
        if (boton.matches('[data-uso-retirar]')) {
            const fila = boton.closest('[data-uso-fila]');
            const cantidad = fila.querySelector('[data-uso-cantidad]');
            const retirado = fila.dataset.retirado !== '1';
            if (retirado) fila.dataset.cantidadPrevia = cantidad.value;
            cantidad.value = retirado ? '0' : fila.dataset.cantidadPrevia;
            cantidad.readOnly = retirado;
            fila.dataset.retirado = retirado ? '1' : '0';
            fila.querySelector('[data-uso-retirado]').hidden = !retirado;
            boton.innerHTML = retirado ? '<i class="fas fa-undo mr-2" aria-hidden="true"></i>Deshacer retiro' : '<i class="fas fa-trash mr-2" aria-hidden="true"></i>Quitar material';
            boton.classList.toggle('btn-danger', !retirado);
            boton.classList.toggle('btn-cancel', retirado);
            actualizar();
            if (retirado && usoMateriales.querySelector('[data-uso-motivo]').required) usoMateriales.querySelector('[data-uso-motivo]').focus();
        }
        if (boton.matches('[data-uso-quitar]')) boton.closest('[data-uso-fila]').remove();
        actualizar();
    });
    usoMateriales.addEventListener('submit', () => {
        usoMateriales.querySelector('button[type="submit"]').disabled = true;
    });
    window.addEventListener('pageshow', () => { usoMateriales.querySelector('button[type="submit"]').disabled = false; });
    actualizar();
}

// Confirmaciones del cierre: los permisos, la versión y las cantidades se verifican en servidor.
document.querySelectorAll('[data-cierre-pedido]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (form.dataset.enviando === '1') { event.preventDefault(); return; }
        event.preventDefault();
        if (!form.reportValidity()) return;
        if (window.Swal) {
            const resultado = await window.Swal.fire({icon: 'question', title: form.dataset.cierreTitulo, text: form.dataset.cierreTexto, showCancelButton: true, confirmButtonText: 'Sí, confirmar', cancelButtonText: 'Cancelar'});
            if (!resultado.isConfirmed) return;
        }
        form.dataset.enviando = '1';
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((boton) => { boton.disabled = true; });
        HTMLFormElement.prototype.submit.call(form);
    });
    window.addEventListener('pageshow', () => { delete form.dataset.enviando; form.querySelectorAll('button').forEach((boton) => { boton.disabled = false; }); });
});

// Al cambiar el número de muebles se vuelve a revisar el precio propuesto.
const formularioCotizacion = document.querySelector('[data-pedido-cotizacion]');
if (formularioCotizacion) {
    formularioCotizacion.addEventListener('change', event => {
        if (!event.target.matches('[data-cantidad-muebles]')) return;
        const propuesta = event.target.closest('[data-detalle]').querySelector('[name$="[precio_propuesto]"]');
        if (propuesta) propuesta.value = '';
    });
}
