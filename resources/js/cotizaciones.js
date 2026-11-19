// Interacciones del formulario de cotizaciones. El guardado confirma los importes.
const cotizacionForm = document.querySelector("[data-cotizacion-form]");
if (cotizacionForm) {
    const detalles = cotizacionForm.querySelector("[data-detalles]");
    const entrada = (value) => {
        const texto = String(value ?? "");
        return /^\d+\.\d+$/.test(texto) ? texto.replace(/0+$/, "").replace(/\.$/, "") : texto;
    };
    const numero = (value, escala) => {
        const texto = String(value ?? "").trim();
        if (!(escala === 0 ? /^\d+$/ : new RegExp("^\\d+(?:\\.\\d{1," + escala + "})?$")).test(texto)) return null;
        const partes = texto.split(".");
        return BigInt(partes[0]) * 10n ** BigInt(escala)
            + BigInt((partes[1] || "").padEnd(escala, "0"));
    };
    const dinero = (value) => value === null ? "Pendiente de revisión"
        : "Bs " + (value / 100n) + "," + (value % 100n).toString().padStart(2, "0");
    const cantidad = (value) => value === null ? "—"
        : (value / 10000n) + ((value % 10000n) === 0n ? "" : "," + (value % 10000n).toString().padStart(4, "0").replace(/0+$/, ""));
    const redondear = (value, divisor) => (value + divisor / 2n) / divisor;
    function renumerar() {
        [...detalles.children].forEach((detalle, indice) => {
            detalle.dataset.index = indice;
            detalle.querySelector("[data-numero-mueble]").textContent = indice + 1;
            detalle.querySelectorAll("[name]").forEach((campo) => {
                campo.name = campo.name.replace(/detalles\[(?:\d+|__D__)\]/, "detalles[" + indice + "]");
            });
            detalle.querySelectorAll("[id],label[for],[aria-describedby]").forEach((elemento) => {
                for (const atributo of ["id", "for", "aria-describedby"]) {
                    if (elemento.hasAttribute(atributo)) {
                        elemento.setAttribute(atributo, elemento.getAttribute(atributo).split(/\s+/).map(valor => valor.replace(/^d-(?:\d+|__D__)-/, "d-" + indice + "-")).join(" "));
                    }
                }
            });
            detalle.querySelectorAll("[data-material-row]").forEach((material, posicion) => {
                material.querySelectorAll("[name]").forEach((campo) => {
                    campo.name = campo.name.replace(/\[materiales\]\[(?:\d+|__M__)\]/, "[materiales][" + posicion + "]");
                });
                material.querySelectorAll("[id],label[for],[aria-describedby]").forEach((elemento) => {
                    for (const atributo of ["id", "for", "aria-describedby"]) {
                        if (elemento.hasAttribute(atributo)) {
                            elemento.setAttribute(atributo, elemento.getAttribute(atributo).split(/\s+/).map(valor => valor.replace(/^m-(?:\d+|__D__)-(?:\d+|__M__)-/, "m-" + indice + "-" + posicion + "-")).join(" "));
                        }
                    }
                });
            });
        });
    }
    function aviso(texto) {
        if (window.Swal) window.Swal.fire({ icon: "info", text: texto });
    }
    function calcular() {
        let total = 0n;
        let sugerido = 0n;
        let pendiente = false;
        const recargoInput = cotizacionForm.querySelector("[data-recargo]");
        const recargo = recargoInput ? numero(recargoInput.value, 4) : null;
        detalles.querySelectorAll("[data-detalle]").forEach((detalle) => {
            const muebles = numero(detalle.querySelector("[data-cantidad-muebles]").value, 0);
            let materialesTotal = 0n;
            let falta = muebles === null || muebles === 0n;
            detalle.querySelectorAll("[data-material-row]").forEach((fila) => {
                const retiro = fila.querySelector('[data-retirar-material]');
                const retirado = retiro?.value === '1';
                if (retiro) {
                    const motivoRetiro = fila.querySelector('[data-motivo-retiro]');
                    fila.querySelector('[data-retiro-material-panel]').hidden = !retirado;
                    motivoRetiro.required = retirado && motivoRetiro.dataset.requiereMotivo === '1';
                    motivoRetiro.minLength = motivoRetiro.required ? 5 : 0;
                    fila.querySelector('[data-cantidad-unitaria]').readOnly = retirado;
                    fila.querySelector('[data-cantidad-aplicada]').readOnly = retirado;
                    if (retirado) {
                        fila.querySelector('[data-cantidad-unitaria]').value = '0';
                        fila.querySelector('[data-cantidad-aplicada]').value = '0';
                    }
                }
                const select = fila.querySelector("[data-material-select]");
                const opcion = select.selectedOptions[0];
                fila.querySelector("[data-unidad-material]").textContent = opcion?.dataset.unidad || "";
                const unitaria = numero(fila.querySelector("[data-cantidad-unitaria]").value, 4);
                const nominal = unitaria === null || muebles === null ? null : unitaria * muebles;
                const aplicadaInput = fila.querySelector("[data-cantidad-aplicada]");
                const aplicada = aplicadaInput.value.trim() ? numero(aplicadaInput.value, 4) : nominal;
                const estado = fila.querySelector("[data-estado-costo]").value;
                const inputCosto = fila.querySelector("[data-costo-material]");
                fila.querySelector("[data-costo-ayuda]").textContent = estado === "pendiente" ? "Costo pendiente: introduce un importe cuando lo conozcas." : "Estimación editable; no cambia el catálogo.";
                fila.querySelector("[data-motivo-cantidad]").required = nominal !== null && aplicada !== null && aplicada !== nominal;
                const costo = numero(inputCosto.value, 6);
                const sinCantidad = cotizacionForm.hasAttribute("data-pedido-cotizacion") && fila.dataset.materialPedido === "1" && aplicada === 0n;
                const subtotal = sinCantidad ? 0n : (estado === "pendiente" || aplicada === null || costo === null ? null : redondear(aplicada * costo, 100000000n));
                fila.querySelector("[data-nominal-material]").textContent = cantidad(nominal);
                fila.querySelector("[data-subtotal-material]").textContent = dinero(subtotal);
                if (subtotal === null) falta = true;
                else materialesTotal += subtotal;
            });
            const manoUnitaria = numero(detalle.querySelector("[data-mano-unitaria]").value, 2);
            const manoNominal = manoUnitaria === null || muebles === null ? null : manoUnitaria * muebles;
            const manoInput = detalle.querySelector("[data-mano-aplicada]");
            const manoAplicada = manoInput.value.trim() ? numero(manoInput.value, 2) : manoNominal;
            const otros = numero(detalle.querySelector("[data-otros-costos]").value, 2);
            detalle.querySelector("[data-motivo-mano]").required = manoNominal !== null && manoAplicada !== null && manoNominal !== manoAplicada;
            detalle.querySelector("[data-nominal-mano]").textContent = dinero(manoNominal);
            detalle.querySelector("[data-total-materiales]").textContent = dinero(falta ? null : materialesTotal);
            detalle.querySelector("[data-total-mano]").textContent = dinero(manoAplicada);
            detalle.querySelector("[data-total-otros]").textContent = dinero(otros);
            const costoDetalle = falta || manoAplicada === null || otros === null ? null : materialesTotal + manoAplicada + otros;
            detalle.querySelector("[data-total-detalle]").textContent = dinero(costoDetalle);
            if (costoDetalle === null) pendiente = true;
            else {
                total += costoDetalle;
                if (recargo !== null) sugerido += redondear(costoDetalle * (1000000n + recargo), 1000000n);
            }
        });
        cotizacionForm.querySelector("[data-total-cotizacion]").textContent = dinero(pendiente ? null : total);
        const sugeridoLabel = cotizacionForm.querySelector("[data-sugerido-cotizacion]");
        if (sugeridoLabel) sugeridoLabel.textContent = pendiente || recargo === null ? "Sin porcentaje configurado o costos pendientes" : dinero(sugerido);
    }
    function seleccionar(fila) {
        const select = fila.querySelector("[data-material-select]");
        const precio = entrada(select.selectedOptions[0]?.dataset.precio);
        const costo = numero(precio, 6);
        fila.querySelector("[data-estado-costo]").value = costo !== null && costo > 0n ? "conocido" : "pendiente";
        fila.querySelector("[data-costo-material]").value = costo !== null && costo > 0n ? precio : "";
        fila.querySelector("[data-fuente-costo]").value = "referencia_catalogo";
    }
    function agregarMaterial(detalle) {
        const lista = detalle.querySelector("[data-materiales]");
        if (lista.children.length >= 50) {
            aviso("Puedes registrar hasta 50 materiales por detalle.");
            return null;
        }
        lista.insertAdjacentHTML("beforeend", document.querySelector("#plantilla-material").innerHTML
            .replaceAll("__D__", detalle.dataset.index)
            .replaceAll("__M__", String(lista.children.length)));
        return lista.lastElementChild;
    }
    function incluirPrincipal(campo) {
        if (!campo.value) return;
        const detalle = campo.closest("[data-detalle]");
        const filas = [...detalle.querySelectorAll("[data-material-row]")];
        if (filas.some(fila => fila.querySelector("[data-material-select]").value === campo.value)) return;
        const fila = filas.find(fila => !fila.querySelector("[data-material-select]").value) || agregarMaterial(detalle);
        if (!fila) return;
        fila.querySelector("[data-material-select]").value = campo.value;
        seleccionar(fila);
        renumerar();
    }
    const camposSimilitud = ["tipo_mueble_id", "cantidad_muebles", "material_principal_id", "largo_cm", "ancho_cm", "alto_cm", "acabado", "puertas", "cajones", "divisiones"];
    const busquedas = new WeakMap();
    const descripcion = detalle => Object.fromEntries(camposSimilitud.map(nombre => [nombre, detalle.querySelector('[name$="[' + nombre + ']"]').value]));
    function estadoBusqueda(detalle, texto) {
        const estado = detalle.querySelector("[data-estado-busqueda]");
        if (estado) estado.textContent = texto;
    }
    function abrirCostos(detalle) {
        const costos = detalle.querySelector("[data-llenado-costos]");
        costos.open = true;
        costos.scrollIntoView({ block: "start", behavior: "smooth" });
    }
    async function buscarCasos(detalle) {
        for (const nombre of camposSimilitud) {
            const campo = detalle.querySelector('[name$="[' + nombre + ']"]');
            if (!campo.checkValidity() || !campo.value.trim()) {
                const seccion = campo.closest("details");
                if (seccion) seccion.open = true;
                campo.reportValidity();
                campo.focus();
                return estadoBusqueda(detalle, "Completa las características del mueble antes de buscar.");
            }
        }
        busquedas.get(detalle)?.abortador.abort();
        const datos = descripcion(detalle);
        const busqueda = { abortador: new AbortController(), descripcion: JSON.stringify(datos), propuestas: [] };
        busquedas.set(detalle, busqueda);
        const resultados = detalle.querySelector("[data-resultados-casos]");
        resultados.replaceChildren();
        const botones = detalle.querySelectorAll("[data-buscar-casos]");
        botones.forEach(boton => boton.disabled = true);
        estadoBusqueda(detalle, "Buscando casos similares…");
        try {
            const respuesta = await fetch(cotizacionForm.dataset.similitudUrl, {
                method: "POST", credentials: "same-origin",
                headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": cotizacionForm.querySelector('[name="_token"]').value },
                body: JSON.stringify({ mueble: datos }), signal: busqueda.abortador.signal,
            });
            if (!detalle.isConnected || busquedas.get(detalle) !== busqueda || busqueda.descripcion !== JSON.stringify(descripcion(detalle))) return;
            if (!respuesta.ok) {
                if (respuesta.status === 422) {
                    const error = await respuesta.json();
                    throw new Error(Object.values(error.errors || {}).flat().join(" ") || "Revisa las características del mueble.");
                }
                throw new Error(respuesta.status === 419 ? "La sesión venció. Recarga la página." : "No se pudo consultar. Verifica tu acceso e inténtalo nuevamente.");
            }
            const salida = await respuesta.json();
            if (!detalle.isConnected || busquedas.get(detalle) !== busqueda || busqueda.descripcion !== JSON.stringify(descripcion(detalle))) return;
            busqueda.propuestas = salida.propuestas;
            resultados.innerHTML = salida.html;
            estadoBusqueda(detalle, salida.cantidad ? "El caso más similar es la recomendación principal. Elígelo o revisa las alternativas." : "No hay una propuesta disponible. Puedes continuar con el llenado manual.");
            if (!salida.cantidad) detalle.querySelector("[data-llenado-costos]").open = true;
        } catch (error) {
            if (error.name !== "AbortError" && busquedas.get(detalle) === busqueda) estadoBusqueda(detalle, error.message || "No se pudo buscar.");
        } finally {
            if (busquedas.get(detalle) === busqueda) botones.forEach(boton => boton.disabled = false);
        }
    }
    async function usarCaso(detalle, indice) {
        const busqueda = busquedas.get(detalle);
        const propuesta = busqueda?.propuestas[indice];
        if (!propuesta?.aplicable || busqueda.descripcion !== JSON.stringify(descripcion(detalle))) return;
        const confirmacion = window.Swal
            ? await window.Swal.fire({ icon: "question", title: "¿Usar esta propuesta?", text: "Se reemplazarán materiales, mano de obra y otros costos de este mueble. Podrás editarlos; el caso original no cambia.", showCancelButton: true, confirmButtonText: "Sí, usar propuesta", cancelButtonText: "Cancelar" })
            : { isConfirmed: window.confirm("¿Reemplazar materiales y costos con la propuesta editable?") };
        if (!confirmacion.isConfirmed || busquedas.get(detalle) !== busqueda || busqueda.descripcion !== JSON.stringify(descripcion(detalle))) return;
        const opciones = document.querySelector("#plantilla-material").content.querySelector("[data-material-select]").options;
        if (propuesta.materiales.some(material => ![...opciones].some(opcion => opcion.value === String(material.material_id)))) {
            return estadoBusqueda(detalle, "El catálogo cambió. Recarga el formulario antes de aplicar.");
        }
        detalle.querySelector("[data-materiales]").replaceChildren();
        for (const material of propuesta.materiales) {
            const fila = agregarMaterial(detalle);
            fila.querySelector("[data-material-select]").value = String(material.material_id);
            fila.querySelector("[data-cantidad-unitaria]").value = entrada(material.cantidad_unitaria);
            fila.querySelector("[data-costo-material]").value = entrada(material.costo_unitario_estimado ?? "");
            fila.querySelector("[data-estado-costo]").value = material.estado_costo;
            fila.querySelector("[data-fuente-costo]").value = material.fuente_costo;
        }
        detalle.querySelector("[data-mano-unitaria]").value = entrada(propuesta.mano_obra_unitaria);
        detalle.querySelector("[data-otros-costos]").value = entrada(propuesta.otros_costos_total);
        detalle.querySelectorAll("[data-cantidad-aplicada],[data-mano-aplicada],[data-motivo-cantidad],[data-motivo-mano]").forEach(campo => campo.value = "");
        detalle.querySelector('[name$="[precio_propuesto]"]').value = "";
        detalle.querySelector("[data-referencia-caso]").value = propuesta.referencia_caso;
        const avisoAplicada = detalle.querySelector("[data-propuesta-aplicada]");
        avisoAplicada.hidden = false;
        avisoAplicada.textContent = "Propuesta del caso " + propuesta.caso_id + " aplicada. Revisa y edita los materiales y costos; el antecedente original no cambia.";
        detalle.querySelectorAll("[data-caso-propuesta]").forEach(tarjeta => {
            const seleccionada = Number(tarjeta.dataset.casoPropuesta) === indice;
            tarjeta.classList.toggle("is-selected", seleccionada);
            tarjeta.querySelector("[data-propuesta-seleccionada]").hidden = !seleccionada;
        });
        renumerar();
        calcular();
        abrirCostos(detalle);
    }
    cotizacionForm.addEventListener("click", event => {
        const boton = event.target.closest("button");
        const detalle = boton?.closest("[data-detalle]");
        if (!detalle) return;
        if (boton.matches("[data-buscar-casos]")) buscarCasos(detalle);
        if (boton.matches("[data-llenado-manual]")) abrirCostos(detalle);
        if (boton.matches("[data-usar-caso]")) usarCaso(detalle, Number(boton.dataset.usarCaso));
    });
    function invalidarBusqueda(event) {
        const campo = event.target;
        if (!camposSimilitud.some(nombre => campo.name?.endsWith("[" + nombre + "]"))) return;
        const detalle = campo.closest("[data-detalle]");
        const busqueda = busquedas.get(detalle);
        if (!busqueda || busqueda.descripcion === JSON.stringify(descripcion(detalle))) return;
        busqueda.abortador.abort();
        busquedas.delete(detalle);
        detalle.querySelector("[data-resultados-casos]").replaceChildren();
        detalle.querySelectorAll("[data-buscar-casos]").forEach(boton => boton.disabled = false);
        estadoBusqueda(detalle, "Las características cambiaron. Vuelve a buscar para actualizar las propuestas.");
        const aplicada = detalle.querySelector("[data-propuesta-aplicada]");
        if (!aplicada.hidden) aplicada.textContent = "Cambiaste la descripción después de aplicar. Revisa materiales y costos o vuelve a buscar; se conserva la procedencia.";
    }
    cotizacionForm.addEventListener("input", invalidarBusqueda);
    cotizacionForm.addEventListener("change", invalidarBusqueda);

    cotizacionForm.addEventListener("click", (event) => {
        const button = event.target.closest("button");
        if (!button) return;
        if (button.matches("[data-agregar-detalle]")) {
            if (detalles.children.length >= 20) return aviso("Puedes registrar hasta 20 detalles en esta cotización.");
            const indice = detalles.children.length;
            detalles.insertAdjacentHTML("beforeend", document.querySelector("#plantilla-detalle").innerHTML.replaceAll("__D__", String(indice)));
        }
        if (button.matches("[data-quitar-detalle]")) {
            if (detalles.children.length === 1) return aviso("Conserva al menos un mueble.");
            button.closest("[data-detalle]").remove();
        }
        if (button.matches("[data-agregar-material]")) {
            const detalle = button.closest("[data-detalle]");
            const fila = agregarMaterial(detalle);
            fila?.querySelector("[data-buscar-material]").focus();
        }
        if (button.matches("[data-quitar-material]")) {
            const fila = button.closest('[data-material-row]');
            const retiro = fila.querySelector('[data-retirar-material]');
            if (retiro) {
                const retirar = retiro.value !== '1';
                const campos = ['[data-cantidad-unitaria]', '[data-cantidad-aplicada]', '[data-motivo-cantidad]'];
                if (retirar) fila.dataset.valoresPrevios = JSON.stringify(campos.map(selector => fila.querySelector(selector).value));
                const anteriores = JSON.parse(fila.dataset.valoresPrevios || '["0","0",""]');
                campos.forEach((selector, indice) => { fila.querySelector(selector).value = retirar ? (indice === 2 ? '' : '0') : anteriores[indice]; });
                retiro.value = retirar ? '1' : '0';
                button.innerHTML = retirar ? '<i class="fas fa-undo mr-2" aria-hidden="true"></i>Deshacer retiro' : '<i class="fas fa-trash mr-2" aria-hidden="true"></i>Quitar';
                button.classList.toggle('btn-danger', !retirar);
                button.classList.toggle('btn-cancel', retirar);
                calcular();
                if (retirar && fila.querySelector('[data-motivo-retiro]').required) fila.querySelector('[data-motivo-retiro]').focus();
            } else {
                const lista = button.closest('[data-materiales]');
                if (lista.children.length === 1) return aviso('Conserva al menos un material por mueble.');
                fila.remove();
            }
        }
        renumerar();
        calcular();
    });
    cotizacionForm.addEventListener("change", (event) => {
        const campo = event.target;
        if (campo.matches("[data-material-select]")) seleccionar(campo.closest("[data-material-row]"));
        if (campo.matches('[name$="[material_principal_id]"]')) {
            incluirPrincipal(campo);
        }
        if (campo.matches("[data-cantidad-muebles]")) {
            const detalle = campo.closest("[data-detalle]");
            detalle.querySelectorAll("[data-cantidad-aplicada],[data-mano-aplicada],[data-motivo-cantidad],[data-motivo-mano]").forEach((input) => input.value = "");
        }
        if (campo.matches("[data-costo-material]") && !campo.readOnly) {
            campo.closest("[data-material-row]").querySelector("[data-fuente-costo]").value = "estimacion_manual";
        }
        calcular();
    });
    cotizacionForm.addEventListener("input", (event) => {
        const campo = event.target;
        const fila = campo.closest("[data-material-row]");
        if (campo.matches("[data-costo-material]")) {
            fila.querySelector("[data-estado-costo]").value = campo.value.trim() ? "conocido" : "pendiente";
            fila.querySelector("[data-fuente-costo]").value = "estimacion_manual";
        }
        if (campo.matches("[data-buscar-material]")) {
            const texto = campo.value.trim().toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            let coincidencias = 0;
            for (const opcion of fila.querySelector("[data-material-select]").options) {
                const coincide = opcion.textContent.toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g, "").includes(texto);
                opcion.hidden = !!opcion.value && !coincide && !opcion.selected;
                if (opcion.value && coincide) coincidencias++;
            }
            fila.querySelector("[data-busqueda-resultado]").textContent = texto ? coincidencias + " materiales encontrados" : "";
        }
        calcular();
    });
    cotizacionForm.addEventListener("invalid", (event) => {
        let seccion = event.target.closest("details");
        while (seccion) {
            seccion.open = true;
            seccion = seccion.parentElement?.closest("details");
        }
    }, true);
    cotizacionForm.addEventListener("submit", renumerar);
    renumerar();
    calcular();
}
