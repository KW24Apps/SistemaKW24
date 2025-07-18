document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('toggleSenha');
    if(toggle){
        toggle.addEventListener('click', function () {
            var senhaInput = document.getElementById('senha');
            var eyeIcon = this.querySelector('i');
            if (senhaInput.type === "password") {
                senhaInput.type = "text";
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = "password";
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    }
});

document.addEventListener("DOMContentLoaded", function() {
    var alert = document.getElementById('loginErrorAlert');
    if(alert){
        alert.style.opacity = 0;
        alert.style.transform = 'translateY(-30px)';
        setTimeout(function(){
            alert.style.transition = 'opacity 0.5s, transform 0.5s';
            alert.style.opacity = 1;
            alert.style.transform = 'translateY(0)';
        }, 100);
        // Esconde suavemente após 4s
        setTimeout(function(){
            alert.style.opacity = 0;
            alert.style.transform = 'translateY(-30px)';
            setTimeout(function(){
                alert.style.display = 'none';
            }, 500);
        }, 4000);
    }
});