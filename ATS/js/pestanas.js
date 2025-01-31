document.addEventListener('DOMContentLoaded', () => {
    const links = document.querySelectorAll('.sidebar a');
    const mainContent = document.getElementById('mainContent');

    if (links.length > 0 && mainContent) {
        links.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');

                if (page) {
                    fetch(page)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');

                            let newContent = doc.querySelector('.main-content') || doc.body;
                            if (newContent) {
                                mainContent.innerHTML = newContent.innerHTML;
                                ejecutarScripts(mainContent);
                                loadStyles();

                                // ✅ Si volvemos a "Solicitante.php", recargar datos del usuario
                                if (page === 'Solicitante.php' && window.fetchUserData) {
                                    setTimeout(() => {
                                        window.fetchUserData();
                                    }, 100); // Pequeño delay para asegurar la carga del DOM
                                }
                            } else {
                                console.error('No se encontró contenido en la página cargada.');
                            }
                        })
                        .catch(error => console.error('Error al cargar la página:', error));
                }
            });
        });
    }

    function ejecutarScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
                newScript.async = true;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            document.body.appendChild(newScript);
            document.body.removeChild(newScript);
        });

        // 🔥 Volver a cargar los datos del usuario si fetchUserData está disponible
        if (window.fetchUserData) {
            window.fetchUserData();
        }
    }

    function loadStyles() {
        let link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = "css/estilosSolicitante.css";
        document.head.appendChild(link);
    }
});
