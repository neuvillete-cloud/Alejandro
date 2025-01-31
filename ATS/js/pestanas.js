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

                            // Buscamos el contenido de la nueva página dentro de .main-content
                            let newContent = doc.querySelector('.main-content');
                            if (!newContent) {
                                newContent = doc.body; // Si no tiene .main-content, usamos el body entero
                            }

                            if (newContent) {
                                mainContent.innerHTML = newContent.innerHTML; // Reemplazamos solo el contenido
                                ejecutarScripts(mainContent);
                                loadStyles();

                                // ✅ Si volvemos a la página principal, actualizamos los datos
                                if (page === 'Solicitante.php' && window.fetchUserData) {
                                    window.fetchUserData();
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

    // 🔄 Ejecutar scripts en la nueva pestaña cargada
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

        // 🔥 Volvemos a rellenar los datos después de cambiar de pestaña
        if (window.fetchUserData) {
            window.fetchUserData();
        }
    }

    // 🎨 Función para recargar los estilos y evitar que desaparezcan
    function loadStyles() {
        let link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = "css/estilosSolicitante.css";
        document.head.appendChild(link);
    }
});
