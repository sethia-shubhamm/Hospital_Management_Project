document.addEventListener('DOMContentLoaded', function() {
    
    if (document.getElementById('sidebarCollapse')) {
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });
    }

    
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    
    
    const profileNameElement = document.querySelector('.profile-info .name');
    if (profileNameElement && userData.name) {
        profileNameElement.textContent = userData.name;
    }

    
    const logoutBtn = document.querySelector('.logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            localStorage.removeItem('userData');
            
            window.location.href = '/adminLogin/index.html';
        });
    }

    
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}); 