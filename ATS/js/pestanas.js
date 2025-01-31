document.addEventListener('DOMContentLoaded', () => {
    const links = document.querySelectorAll('.sidebar a');
    const mainContent = document.getElementById('mainContent');

    if (links.length > 0 && mainContent) {
        links.forEach(link => {
            link.addEventListener('click', async function (e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');

                if (page) {
                    try {
                        // Si el usuario regresa a la página principal, recargamos la página
                        if (page === 'Solicitante.php') {
                            location.reload(); // 🔄 Recarga la página completamente
                            return; // Detenemos la ejecución para evitar una doble carga
                        }

                        // Cargar el contenido de la nueva página
                        const response = await fetch(page);
                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        let newContent = doc.querySelector('.main-content') || doc.body;
                        if (newContent) {
                            // Reemplazamos el contenido principal
                            mainContent.innerHTML = newContent.innerHTML;

                            // Ejecutamos los scripts de la nueva página
                            await ejecutarScripts(mainContent);

                            // Recargamos los estilos
                            loadStyles();
                        } else {
                            console.error('No se encontró contenido en la página cargada.');
                        }
                    } catch (error) {
                        console.error('Error al cargar la página:', error);
                    }
                }
            });
        });
    }

    // Esta función ejecuta los scripts de la nueva página cargada dinámicamente
    async function ejecutarScripts(container) {
        const scripts = container.querySelectorAll('script');
        for (const oldScript of scripts) {
            const newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
                newScript.async = true;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            document.body.appendChild(newScript);
            document.body.removeChild(newScript);
        }
    }

    // Función para recargar los estilos y evitar que desaparezcan
    function loadStyles() {
        let link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = "css/estilosSolicitante.css";
        document.head.appendChild(link);
    }
});
