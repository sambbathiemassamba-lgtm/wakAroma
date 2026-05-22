/**
 * ===========================================================
 * PAGE CONFIRMATION
 * ===================================================
 */
const inputs = document.querySelectorAll('.code-input');
const hiddenInput = document.getElementById('conf');

inputs.forEach((input, index) => {

    input.addEventListener('input', () => {

        // passer au champ suivant
        if (input.value.length === 1 && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }

        updateCode();
    });

    // retour arrière
    input.addEventListener('keydown', (e) => {

        if (e.key === 'Backspace' && input.value === '' && index > 0) {
            inputs[index - 1].focus();
        }
    });
});

function updateCode() {

    let code = '';

    inputs.forEach(input => {
        code += input.value;
    });

    hiddenInput.value = code;
}

