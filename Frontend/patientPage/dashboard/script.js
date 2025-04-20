document.addEventListener('DOMContentLoaded', function() {
    
    setupLogoutButton();
    
    
    setupPaymentButtons();
});

/**
 * Setup event listeners for payment buttons
 */
function setupPaymentButtons() {
    
    const payButtons = document.querySelectorAll('.pay-bill-btn');
    
    
    payButtons.forEach(button => {
        button.addEventListener('click', function() {
            const billId = this.getAttribute('data-bill-id');
            handlePayBill(billId);
        });
    });
}

/**
 * Handle pay bill button click
 * @param {string} billId - ID of the bill to pay
 */
function handlePayBill(billId) {
    
    if (confirm(`Are you sure you want to pay Bill #${billId}?`)) {
        
        alert(`Payment initiated for Bill #${billId}. You will be redirected to the payment gateway.`);
        
        
    }
}

/**
 * Setup logout button functionality
 */
function setupLogoutButton() {
    const logoutButton = document.querySelector('.logout');
    if (logoutButton) {
        logoutButton.addEventListener('click', function() {
            
            window.location.href = "../../logout.php";
        });
    }
}


document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});
