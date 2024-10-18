function validarinput(idInput) {
    const inputElement = document.getElementById(idInput);
    if (inputElement) {
        const inputValue = inputElement.value.trim();  // Corregido imputValue a inputValue
        const feedbackElement = inputElement.parentElement.querySelector('.invalid-feedback');

        // Si el valor está vacío, muestra el mensaje de error
        if (!inputValue) {
            inputElement.classList.add("is-invalid");
            inputElement.parentElement.classList.add("has-error");
            if (feedbackElement) {
                feedbackElement.textContent = inputElement.getAttribute('data-error');
                feedbackElement.style.display = "block";
            }
            return false;
        } else {
            // Si el valor no está vacío, remueve los errores
            inputElement.classList.remove('is-invalid');
            inputElement.parentElement.classList.remove('has-error');
            if (feedbackElement) {
                feedbackElement.style.display = "none";
            }
            return true;
        }
    }
}
