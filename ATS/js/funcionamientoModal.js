document.addEventListener('DOMContentLoaded', async () => {
    const modal = document.getElementById('profileModal');
    const openModalBtn = document.querySelector('#profileDropdown a:first-child');
    const closeModalBtn = document.getElementById('closeModal');
    const userNameHeader = document.getElementById('userNameHeader');
    const nombreInput = document.getElementById('nombre');
    const areaInput = document.getElementById('area');

    // 🔥 Función para obtener datos del usuario
    async function fetchUserData() {
        try {
            const response = await fetch('dao/daoModal.php');
            const data = await response.json();

            if (data.status === 'success') {
                const { Nombre: nombre, NumNomina: numNomina, Area: area } = data.perfil;

                // Actualiza el encabezado con el nombre del usuario
                if (userNameHeader) userNameHeader.textContent = nombre;

                // Rellena los campos del formulario
                if (nombreInput) nombreInput.value = nombre;
                if (areaInput) areaInput.value = area;

                // Actualiza el modal con los datos del usuario
                document.getElementById('userName').textContent = nombre;
                document.getElementById('userNumNomina').textContent = numNomina;
                document.getElementById('userArea').textContent = area;

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

    // 🛠️ Ejecutamos fetchUserData al inicio para llenar los datos
    await fetchUserData();

    // Evento para abrir el modal con los datos actualizados
    openModalBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        const userData = await fetchUserData();

        if (userData) {
            modal.style.display = 'flex';
        }
    });

    // Evento para cerrar el modal
    closeModalBtn.addEventListener('click', () => modal.style.display = 'none');

    // Cierra el modal al hacer clic fuera de él
    window.addEventListener('click', (event) => {
        if (event.target === modal) modal.style.display = 'none';
    });

    // 🔄 Exportar la función para que la use `pestanas.js`
    window.fetchUserData = fetchUserData;
});


async function precarga() {

    const modal = document.getElementById('profileModal');
    const openModalBtn = document.querySelector('#profileDropdown a:first-child');
    const closeModalBtn = document.getElementById('closeModal');
    const userNameHeader = document.getElementById('userNameHeader');
    const nombreInput = document.getElementById('nombre');
    const areaInput = document.getElementById('area');

    // 🔥 Función para obtener datos del usuario
    async function fetchUserData() {
        try {
            const response = await fetch('dao/daoModal.php');
            const data = await response.json();

            if (data.status === 'success') {
                const { Nombre: nombre, NumNomina: numNomina, Area: area } = data.perfil;

                // Actualiza el encabezado con el nombre del usuario
                if (userNameHeader) userNameHeader.textContent = nombre;

                // Rellena los campos del formulario
                if (nombreInput) nombreInput.value = nombre;
                if (areaInput) areaInput.value = area;

                // Actualiza el modal con los datos del usuario
                document.getElementById('userName').textContent = nombre;
                document.getElementById('userNumNomina').textContent = numNomina;
                document.getElementById('userArea').textContent = area;

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

    // 🛠️ Ejecutamos fetchUserData al inicio para llenar los datos
    await fetchUserData();

    // Evento para abrir el modal con los datos actualizados
    openModalBtn.addEventListener('click', async (event) => {
        event.preventDefault();
        const userData = await fetchUserData();

        if (userData) {
            modal.style.display = 'flex';
        }
    });

    // Evento para cerrar el modal
    closeModalBtn.addEventListener('click', () => modal.style.display = 'none');

    // Cierra el modal al hacer clic fuera de él
    window.addEventListener('click', (event) => {
        if (event.target === modal) modal.style.display = 'none';
    });

    // 🔄 Exportar la función para que la use `pestanas.js`
    window.fetchUserData = fetchUserData;
}
