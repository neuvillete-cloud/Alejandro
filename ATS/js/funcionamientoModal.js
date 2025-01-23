document.addEventListener('DOMContentLoaded', async () => {
    const modal = document.getElementById('profileModal');
    const openModalBtn = document.querySelector('#profileDropdown a:first-child'); // Botón "Ver Perfil"
    const closeModalBtn = document.getElementById('closeModal');
    const userNameHeader = document.getElementById('userNameHeader'); // Contenedor del nombre en el encabezado

    // Función para obtener datos del usuario
    async function fetchUserData() {
        try {
            const response = await fetch('dao/daoModal.php');
            const data = await response.json();

            if (data.status === 'success') {
                const { Nombre: nombre, NumNomina: numNomina, Area: area } = data.perfil;

                // Actualiza el encabezado con el nombre del usuario
                if (userNameHeader) {
                    userNameHeader.textContent = nombre;
                }

                return { nombre, numNomina, area };
            } else {
                console.error('Error al obtener datos:', data.message);
                return null;
            }
        } catch (error) {
            console.error('Error al llamar al endpoint:', error);
            return null;
        }
    }

    // Llamar a fetchUserData al cargar la página para el encabezado
    const userData = await fetchUserData();

    // Evento para abrir el modal
    openModalBtn.addEventListener('click', async (event) => {
        event.preventDefault();

        if (userData) {
            const { nombre, numNomina, area } = userData;

            // Rellenar los datos en el modal
            document.getElementById('userName').textContent = nombre;
            document.getElementById('userNumNomina').textContent = numNomina;
            document.getElementById('userArea').textContent = area;

            // Mostrar el modal
            modal.style.display = 'flex';
        } else {
            console.error('No se pudieron obtener los datos del usuario para el modal.');
        }
    });

    // Evento para cerrar el modal
    closeModalBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Cierra el modal al hacer clic fuera de él
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
