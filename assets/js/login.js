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

