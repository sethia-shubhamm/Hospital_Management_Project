const sliderButtons = document.querySelectorAll('.slider button');

sliderButtons.forEach(button => {
    button.addEventListener('click', () => {
        sliderButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginStatus = document.getElementById('loginStatus');
    const demoButton = document.getElementById('demoButton');
    
    
    if (demoButton) {
        demoButton.addEventListener('click', function() {
            document.getElementById('email').value = 'doctor@hospital.com';
            document.getElementById('password').value = 'doctor123';
            
            
            if (loginForm) {
                loginForm.submit();
            }
        });
    }
    
    
});

