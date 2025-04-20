document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const donateForm = document.querySelector('#donate .blood-form');
    const receiveForm = document.querySelector('#receive .blood-form');
    const inventoryTable = document.querySelector('.inventory-table tbody');
    const bloodGroupFilter = document.getElementById('filter-blood-group');
    const statusFilter = document.getElementById('filter-status');
    
    // Handle tab switching
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Show the corresponding pane
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
            
            // If inventory tab is opened, display sample inventory data
            if (tabId === 'inventory') {
                displaySampleBloodInventory();
            }
        });
    });
    
    // Display sample blood inventory data for UI design
    function displaySampleBloodInventory() {
        // Sample inventory data
        const sampleInventory = [
            { BLOOD_TYPE: 'A+', QUANTITY: 25, EXPIRY_DATE: '2023-12-15', status: 'available' },
            { BLOOD_TYPE: 'B+', QUANTITY: 15, EXPIRY_DATE: '2023-12-20', status: 'available' },
            { BLOOD_TYPE: 'O+', QUANTITY: 30, EXPIRY_DATE: '2023-11-30', status: 'low' },
            { BLOOD_TYPE: 'AB+', QUANTITY: 10, EXPIRY_DATE: '2023-12-10', status: 'critical' },
            { BLOOD_TYPE: 'A-', QUANTITY: 12, EXPIRY_DATE: '2023-12-05', status: 'available' },
            { BLOOD_TYPE: 'B-', QUANTITY: 8, EXPIRY_DATE: '2023-11-25', status: 'critical' },
            { BLOOD_TYPE: 'O-', QUANTITY: 18, EXPIRY_DATE: '2023-12-12', status: 'low' },
            { BLOOD_TYPE: 'AB-', QUANTITY: 5, EXPIRY_DATE: '2023-12-18', status: 'critical' }
        ];
        
        if (!inventoryTable) return;
            
        // Get filter values
        const bloodGroup = bloodGroupFilter ? bloodGroupFilter.value : 'all';
        const status = statusFilter ? statusFilter.value : 'all';
        
        // Apply filters
        let filteredInventory = sampleInventory;
        if (bloodGroup !== 'all') {
            filteredInventory = filteredInventory.filter(item => item.BLOOD_TYPE === bloodGroup);
        }
        if (status !== 'all') {
            filteredInventory = filteredInventory.filter(item => item.status === status);
        }
        
        // If no results after filtering
        if (filteredInventory.length === 0) {
            inventoryTable.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center">No results match your filter criteria</td>
                </tr>
            `;
            return;
        }
        
        // Format date function
        const formatDate = (dateString) => {
            const date = new Date(dateString);
            return date.toLocaleString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric'
            });
        };
        
        // Generate table rows
        const tableRows = filteredInventory.map(item => `
            <tr>
                <td>${item.BLOOD_TYPE}</td>
                <td>${item.QUANTITY} units</td>
                <td>${formatDate(item.EXPIRY_DATE)}</td>
                <td><span class="status ${item.status}">${item.status.charAt(0).toUpperCase() + item.status.slice(1)}</span></td>
            </tr>
        `).join('');
        
        inventoryTable.innerHTML = tableRows;
    }
    
    // Initialize inventory filters
    if (bloodGroupFilter && statusFilter) {
        bloodGroupFilter.addEventListener('change', function() {
            displaySampleBloodInventory();
        });
        
        statusFilter.addEventListener('change', function() {
            displaySampleBloodInventory();
        });
    }
    
    // Handle donate form submission
    if (donateForm) {
        donateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data for UI demo
            const donorName = document.getElementById('donor-name').value;
            const bloodType = document.getElementById('donor-blood-group').value;
            
            // Show success message
            alert(`Thank you for your donation, ${donorName}! Your ${bloodType} blood donation has been registered. We will contact you shortly.`);
            this.reset();
            
            // Switch to inventory tab to show updated data
            const inventoryTab = document.querySelector('[data-tab="inventory"]');
            if (inventoryTab) {
                inventoryTab.click();
            }
        });
    }
    
    // Handle receive form submission
    if (receiveForm) {
        receiveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data for UI demo
            const patientName = document.getElementById('patient-name').value;
            const bloodType = document.getElementById('required-blood-group').value;
            const units = document.getElementById('blood-units').value;
            
            // Show success message with sample reference number
            const refNumber = 'REQ-' + Math.floor(1000 + Math.random() * 9000);
            alert(`Blood request for ${units} units of ${bloodType} for ${patientName} submitted successfully! Reference number: ${refNumber}`);
            this.reset();
            
            // Switch to inventory tab
            const inventoryTab = document.querySelector('[data-tab="inventory"]');
            if (inventoryTab) {
                inventoryTab.click();
            }
        });
    }
    
    // Display login/profile section for UI design
    function updateUIForDesign() {
        const loginButtons = document.querySelector('.login');
        if (!loginButtons) return;
        
        // Default UI for not logged in
        loginButtons.innerHTML = `
            <a href="/login/index.html" class="btn btn-primary">Login</a>
            <a href="/signUp/index.html" class="btn btn-outline-secondary">Sign Up</a>
        `;
        
        // Add ability to "simulate" logged in user for UI testing
        const simLoginBtn = document.createElement('button');
        simLoginBtn.className = 'btn btn-sm btn-info mt-2';
        simLoginBtn.textContent = 'Simulate Logged In';
        simLoginBtn.style.display = 'block';
        simLoginBtn.style.marginLeft = 'auto';
        simLoginBtn.style.marginRight = 'auto';
        
        simLoginBtn.addEventListener('click', function() {
            loginButtons.innerHTML = `
                <button type="button" class="btn btn-primary" id="viewProfileBtn">
                    My Profile
                </button>
                <button type="button" class="btn btn-outline-danger" id="logoutBtn">
                    Logout
                </button>
            `;
            
            document.getElementById('viewProfileBtn').addEventListener('click', function() {
                window.location.href = '/patientPage/dashboard/index.html';
            });
            
            document.getElementById('logoutBtn').addEventListener('click', function() {
                updateUIForDesign();
            });
        });
        
        loginButtons.appendChild(simLoginBtn);
    }
    
    // Initialize by showing sample inventory data if on inventory tab
    if (document.querySelector('.tab-btn.active').getAttribute('data-tab') === 'inventory') {
        displaySampleBloodInventory();
    }
    
    // Setup UI
    updateUIForDesign();
}); 