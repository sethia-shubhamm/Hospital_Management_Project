

document.addEventListener('DOMContentLoaded', function() {
    
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 992) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnMenuToggle = menuToggle.contains(event.target);
            
            if (sidebar.classList.contains('active') && !isClickInsideSidebar && !isClickOnMenuToggle) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992 && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });
    
    
    simulateChartData();
});

