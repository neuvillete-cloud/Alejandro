document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('profileModal');
    const openModalBtn = document.querySelector('#profileDropdown a:first-child'); // Botón "Ver Perfil"
    const closeModalBtn = document.getElementById('closeModal');

    openModalBtn.addEventListener('click', async (event) => {
        event.preventDefault();

        // Llamar al DAO para obtener los datos del usuario
        const response = await fetch('dao/daoModal.php');
        const userData = await response.json();

        // Rellenar los datos en el modal
        document.getElementById('userName').textContent = userData.nombre;
        document.getElementById('userNumNomina').textContent = userData.numNomina;
        document.getElementById('userArea').textContent = userData.area;

        // Mostrar el modal
        modal.style.display = 'block';
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
