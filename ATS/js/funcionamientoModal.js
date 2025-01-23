document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('profileModal');
    const openModalBtn = document.querySelector('#profileDropdown a:first-child'); // Botón "Ver Perfil"
    const closeModalBtn = document.getElementById('closeModal');

    openModalBtn.addEventListener('click', async (event) => {
        event.preventDefault();

        // Llamar al DAO para obtener los datos del usuario
        try {
            const response = await fetch('dao/daoModal.php');
            const data = await response.json();

            if (data.status === 'success') {
                const { nombre, numNomina, area } = data.perfil;

                // Rellenar los datos en el modal
                document.getElementById('userName').textContent = nombre;
                document.getElementById('userNumNomina').textContent = numNomina;
                document.getElementById('userArea').textContent = area;

                // Mostrar el modal
                modal.style.display = 'block';
            } else {
                console.error('Error al obtener datos:', data.message);
            }
        } catch (error) {
            console.error('Error al llamar al endpoint:', error);
        }
    });

    closeModalBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
