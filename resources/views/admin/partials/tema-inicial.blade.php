<script>
    // Aplicar la preferencia antes de mostrar la página evita destellos entre temas.
    (() => {
        let tema = 'light';
        try { if (localStorage.getItem('tajibo.tema') === 'dark') tema = 'dark'; } catch {}
        document.documentElement.dataset.theme = tema;
        document.documentElement.style.colorScheme = tema;
    })();
</script>
