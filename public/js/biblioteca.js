/**
 * biblioteca.js — Filtro interactivo, estado de descargas y subida de aportes.
 */
(function () {
    function actualizarBotonesDescargados() {
        try {
            const desc = JSON.parse(localStorage.getItem('lay8_descargados') || '[]');
            document.querySelectorAll('.btn-descarga-item').forEach((btn) => {
                const id = parseInt(btn.getAttribute('data-aporte-id'), 10);
                if (desc.includes(id)) {
                    const badge = document.createElement('span');
                    badge.className = 'badge-aporte-guardado';
                    badge.setAttribute('data-aporte-id', String(id));
                    badge.title = 'Ya tienes este archivo descargado en tu dispositivo';
                    badge.innerHTML = '<i class="bx bx-check-double"></i> Guardado';
                    btn.replaceWith(badge);
                }
            });
        } catch (e) {}
    }

    window.marcarAporteDescargado = function (event, id) {
        try {
            const desc = JSON.parse(localStorage.getItem('lay8_descargados') || '[]');
            if (desc.includes(id)) {
                if (event) event.preventDefault();
                return false;
            }
            desc.push(id);
            localStorage.setItem('lay8_descargados', JSON.stringify(desc));
            setTimeout(actualizarBotonesDescargados, 80);
        } catch (e) {}
        return true;
    };

    window.mostrarNombreArchivoAporte = function (input) {
        const contenedor = document.getElementById('nombre-archivo-seleccionado');
        const texto = document.getElementById('texto-nombre');
        if (!contenedor || !texto) return;
        if (input?.files && input.files[0]) {
            texto.textContent = input.files[0].name;
            contenedor.style.display = 'block';
        } else {
            contenedor.style.display = 'none';
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const inputVivo = document.getElementById('buscador-vivo');
        const tarjetas = document.querySelectorAll('.tarjeta-aporte');

        if (inputVivo && tarjetas.length > 0) {
            inputVivo.addEventListener('input', function () {
                const termino = this.value.toLowerCase().trim();
                tarjetas.forEach(function (card) {
                    const titulo = card.getAttribute('data-titulo') || '';
                    const autor = card.getAttribute('data-autor') || '';
                    const archivo = card.getAttribute('data-archivo') || '';
                    card.style.display =
                        titulo.includes(termino) || autor.includes(termino) || archivo.includes(termino)
                            ? 'flex'
                            : 'none';
                });
            });
        }

        actualizarBotonesDescargados();
    });
})();
