document.addEventListener('DOMContentLoaded', function() {
    
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    
    handleResponsiveSidebar();
    
    
    addTableHoverEffect();
});


function addTableHoverEffect() {
    const tableRows = document.querySelectorAll('.table tbody tr');
    if (tableRows) {
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(114, 96, 255, 0.05)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    }
}


function handleResponsiveSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    
    checkScreenSize();
    
    
    window.addEventListener('resize', function() {
        checkScreenSize();
    });
    
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        });
    }
}


function checkScreenSize() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
        }
    }
}


function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}


function formatTime(timeString) {
    const time = new Date(`2000-01-01T${timeString}`);
    return time.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
} 