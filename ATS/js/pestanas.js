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
                            ejecutarScripts(mainContent);
                            loadStyles();

                            // ✅ Si volvemos a la página del formulario, rellenamos los datos nuevamente
                            if (page === 'Solicitante.php' && window.fetchUserData) {
                                await fetchUserData();
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

        // ✅ Llamamos a `fetchUserData` otra vez para recargar los datos en los campos del formulario
        if (window.fetchUserData) {
            await fetchUserData();
        }
    }

    function loadStyles() {
        let link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = "css/estilosSolicitante.css";
        document.head.appendChild(link);
    }
});
