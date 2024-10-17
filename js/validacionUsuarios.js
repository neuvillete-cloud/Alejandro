function validarinput(idInput){
    const inputElement = document.getElementById(idInput);
    if (inputElement) {
        const imputValue =inputElement.value.trim()
        const feedbackelement = inputElement.parentElement.querySelector('.invalid-feedback')

        if(!imputValue){
            inputElement.classList.add("is-invalid")
            inputElement.parentElement.classList.add("has-error")
            if(feedbackelement){
                feedbackelement.textContent = inputElement.getAttribute('data-error')
                feedbackelement.style.display = "block"
            }
            return false
        }else{
            inputElement.classList.remove('is-invalid')
            inputElement.parentElement.classList.remove('has-error')
            if (feedbackelement){
                feedbackelement.style.display = "none"
            }
            return true
        }

    }

}

