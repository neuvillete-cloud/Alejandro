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
                        const response = await fetch(page);
                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        let newContent = doc.querySelector('.main-content') || doc.body;
                        if (newContent) {
                            mainContent.innerHTML = newContent.innerHTML;

                            // Ejecutar los scripts de la página cargada
                            await ejecutarScripts(mainContent);

                            // Recargar los estilos
                            loadStyles();

                            // Ahora, después de cargar la nueva página, recargamos los datos del formulario
                            // Solo lo hacemos en la página principal o la página relevante
                            if (page === 'Solicitante.php') {
                                await fetchUserData();  // Asegurémonos de que los datos se recarguen
                            }
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

    // Esta función se encargará de ejecutar los scripts de la nueva página
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

        // Asegurarnos de que se llamen los datos del usuario después de cargar los scripts
        await fetchUserData();
    }

    // Función para recargar los estilos y evitar que desaparezcan
    function loadStyles() {
        let link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = "css/estilosSolicitante.css";
        document.head.appendChild(link);
    }
});
