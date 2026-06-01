/*
|--------------------------------------------------------------------------
| TIMER
|--------------------------------------------------------------------------
*/
let timeLeft = Math.max(0, parseInt(remainingTime, 10) || 0);

const timer = document.getElementById("timer");

const resendBlock = document.getElementById("resend-block");

const btnConfirm = document.getElementById("btn-confirm");

/*
|--------------------------------------------------------------------------
| SHOW RESEND LINK
|--------------------------------------------------------------------------
*/
function showResendLink()
{
    resendBlock.innerHTML = `
        <a href="resendCode.php" class="resend-link">
            Envoyer un nouveau code
        </a>
    `;

    btnConfirm.disabled = true;
}

/*
|--------------------------------------------------------------------------
| COUNTDOWN
|--------------------------------------------------------------------------
*/
if (timer) {

    function updateTimer() {

        // expiration
        if (timeLeft <= 0) {

            timer.innerHTML = "Expiré";

            showResendLink();

            clearInterval(countdown);

            return;
        }

        let minutes = Math.floor(timeLeft / 60);

        let seconds = timeLeft % 60;

        seconds = seconds < 10
            ? "0" + seconds
            : seconds;

        timer.innerHTML = minutes + ":" + seconds;

        timeLeft--;
    }

    updateTimer();

    const countdown = setInterval(updateTimer, 1000);
}

/*
|--------------------------------------------------------------------------
| CODE INPUT SYSTEM
|--------------------------------------------------------------------------
*/
const inputs = document.querySelectorAll(".code-input");

const hiddenInput = document.getElementById("conf");

/*
|--------------------------------------------------------------------------
| UPDATE CODE
|--------------------------------------------------------------------------
*/
function updateCode()
{
    let code = "";

    inputs.forEach(input => {
        code += input.value;
    });

    hiddenInput.value = code;

    if (code.length === 6 && timeLeft > 0) {

        btnConfirm.disabled = false;

    } else if (code.length < 6) {

        btnConfirm.disabled = true;

        // Ne pas re-désactiver si le timer vient d'expirer pendant la saisie :
        // la désactivation est gérée par showResendLink()
    }
}

/*
|--------------------------------------------------------------------------
| EVENTS
|--------------------------------------------------------------------------
*/
inputs.forEach((input, index) => {

    input.addEventListener("input", () => {

        input.value = input.value.replace(/[^a-zA-Z0-9]/g, "");

        if (
            input.value.length === 1 &&
            index < inputs.length - 1
        ) {
            inputs[index + 1].focus();
        }

        updateCode();
    });

    input.addEventListener("keydown", (e) => {

        if (
            e.key === "Backspace" &&
            input.value === "" &&
            index > 0
        ) {
            inputs[index - 1].focus();
        }
    });

    input.addEventListener("paste", (e) => {

        e.preventDefault();

        const pasted = e.clipboardData
            .getData("text")
            .replace(/[^a-zA-Z0-9]/g, "")
            .slice(0, 6);

        pasted.split("").forEach((char, i) => {

            if (inputs[i]) {
                inputs[i].value = char;
            }
        });

        updateCode();
    });

});
