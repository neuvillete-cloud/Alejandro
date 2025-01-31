document.addEventListener('DOMContentLoaded', () => {
    const links = document.querySelectorAll('.sidebar a');
    const mainContent = document.getElementById('mainContent');

    if (links.length > 0 && mainContent) {
        links.forEach(link => {
            link.addEventListener('click', async function (e) {  // 🔥 Hacemos el callback `async`
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

                            // ✅ Esperar a que fetchUserData termine antes de continuar
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

        // 🔥 Esperamos a que fetchUserData termine antes de continuar
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
