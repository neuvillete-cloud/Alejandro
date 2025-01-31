document.addEventListener('DOMContentLoaded', () => {
    const links = document.querySelectorAll('.sidebar a');
    const mainContent = document.getElementById('mainContent');

    // Función para verificar si estamos en la página principal y recargar los datos
    const cargarDatosPaginaPrincipal = async (page) => {
        if (page === 'Solicitante.php') {
            if (window.fetchUserData) {
                await fetchUserData();  // Vuelve a cargar los datos del usuario
            } else {
                console.warn('fetchUserData no está definido en este momento.');
            }
        }
    };

    if (links.length > 0 && mainContent) {
        links.forEach(link => {
            link.addEventListener('click', async function (e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');

                if (page) {
                    try {
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

                            // Llamamos a la función que cargará los datos si estamos en la página principal
                            await cargarDatosPaginaPrincipal(page);
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

        // Asegurémonos de que se recarguen los datos del usuario después de ejecutar los scripts
        if (window.fetchUserData) {
            await fetchUserData();
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
