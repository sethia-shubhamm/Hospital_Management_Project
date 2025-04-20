const sliderButtons = document.querySelectorAll('.slider button');

if (sliderButtons.length > 0) {
    sliderButtons.forEach(button => {
        button.addEventListener('click', () => {
            
            sliderButtons.forEach(btn => btn.classList.remove('active'));
            
            button.classList.add('active');
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const signUpForm = document.getElementById('signUpForm');
    const signUpStatus = document.getElementById('signUpStatus');

    if (signUpForm) {
        console.log('Form found, attaching listeners');
        
        
        signUpForm.addEventListener('submit', function(e) {
            console.log('Form submitted');
            
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            signUpStatus.innerHTML = ''; 
            
            
            if (!name || !email || !password) {
                e.preventDefault();
                signUpStatus.innerHTML = '<div class="alert alert-danger">Please fill in all required fields</div>';
                console.log('Form submission prevented: missing fields');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                signUpStatus.innerHTML = '<div class="alert alert-danger">Passwords do not match</div>';
                console.log('Form submission prevented: passwords do not match');
                return false;
            }
            
            console.log('Form validation passed, continuing submission');
            return true;
        });
    } else {
        console.log('Form not found');
    }
});