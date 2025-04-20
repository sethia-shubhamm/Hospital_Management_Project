// Global variables
let currentPatient = null;
let billingSummary = null;
let billsContainer = null;
let billDetailModal = null;
let billsList = [];
let paymentsList = [];
let selectedBillTimeframe = 3; // Default to last 3 months

// DOM Elements
const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');
const sidebarToggle = document.getElementById('sidebarToggle');
const loadingSpinner = document.getElementById('loadingSpinner');
const errorAlert = document.getElementById('errorAlert');
const toastContainer = document.getElementById('toastContainer');
const logoutButtons = document.querySelectorAll('.logout');

// Billing elements
const billingSummarySection = document.getElementById('billingSummary');
const outstandingBalance = document.getElementById('outstandingBalance');
const lastPaymentAmount = document.getElementById('lastPaymentAmount');
const lastPaymentDate = document.getElementById('lastPaymentDate');
const totalPaidAmount = document.getElementById('totalPaidAmount');
const paymentCount = document.getElementById('paymentCount');
const yearLabel = document.getElementById('yearLabel');

// Bills list elements
const recentBillsSection = document.getElementById('recentBillsSection');
const billTimeFilter = document.getElementById('billTimeFilter');
const billsLoadingSpinner = document.getElementById('billsLoadingSpinner');
const billsErrorMessage = document.getElementById('billsErrorMessage');
const noBillsMessage = document.getElementById('noBillsMessage');
const billsList_el = document.getElementById('billsList');

// Payment modal elements
const modalOutstandingBalance = document.getElementById('modalOutstandingBalance');
const paymentForm = document.getElementById('paymentForm');
const paymentAmount = document.getElementById('paymentAmount');
const paymentMethod = document.getElementById('paymentMethod');
const cardDetails = document.getElementById('cardDetails');
const bankDetails = document.getElementById('bankDetails');

// Initialize when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
    
    // Enable debug info
    const debugInfo = document.getElementById('debugInfo');
    if (debugInfo) debugInfo.style.display = 'block';
});

// Initialize the page
async function initializePage() {
    // Initialize references to DOM elements
    billingSummary = document.getElementById('billingSummary');
    billsContainer = document.getElementById('billsContainer');
    billDetailModal = new bootstrap.Modal(document.getElementById('billDetailModal'));
    
    // Set up event listeners
    setupEventListeners();
    
    // Check authentication then load data
    await checkAuthentication()
        .then(loadPatientData)
        .then(() => {
            // Load billing data after patient data is loaded
            return Promise.all([
                loadBillingSummary(),
                loadBills('all')
            ]);
        })
        .catch(error => {
            console.error('Error initializing page:', error);
            document.getElementById('errorAlert').textContent = 'Failed to initialize page. Please refresh and try again.';
            document.getElementById('errorAlert').style.display = 'block';
            document.getElementById('rawApiData').textContent = `Error initializing page: ${error.message}`;
        });
}

// Set up event listeners
function setupEventListeners() {
    // Toggle sidebar
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.content').classList.toggle('expanded');
        });
    }
    
    // Logout buttons
    logoutButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    });
    
    // Bill time filter change
    billTimeFilter.addEventListener('change', function() {
        selectedBillTimeframe = parseInt(this.value);
        loadBills(selectedBillTimeframe);
    });
    
    // Payment method change
    paymentMethod.addEventListener('change', function() {
        const selectedMethod = this.value;
        
        // Hide all payment detail sections
        cardDetails.style.display = 'none';
        bankDetails.style.display = 'none';
        
        // Show the appropriate section
        if (selectedMethod === 'credit-card' || selectedMethod === 'debit-card') {
            cardDetails.style.display = 'block';
        } else if (selectedMethod === 'bank-transfer') {
            bankDetails.style.display = 'block';
        }
    });
    
    // Payment form submission
    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        processPayment();
    });
}

// Check if user is authenticated
async function checkAuthentication() {
    const authToken = localStorage.getItem('authToken');
    if (!authToken) {
        window.location.href = '/login.html';
        throw new Error('No auth token found');
    }
}

// Load patient data
async function loadPatientData() {
    try {
        // Display debug info about loading patient data
        const rawApiData = document.getElementById('rawApiData');
        if (rawApiData) rawApiData.textContent = 'Loading patient data...\n';
        
        const patientId = localStorage.getItem('userId');
        if (!patientId) {
            throw new Error('No patient ID found');
        }
        
        const response = await fetch(`/api/patients/${patientId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to load patient data');
        }
        
        currentPatient = await response.json();
        
        // Display patient data in debug section
        if (rawApiData) {
            rawApiData.textContent += `Patient Data Response:\n${JSON.stringify(currentPatient, null, 2)}\n\n`;
        }
        
        // Update UI with patient name
        const patientName = document.getElementById('patientName');
        if (patientName && currentPatient.FIRST_NAME && currentPatient.LAST_NAME) {
            patientName.textContent = `${currentPatient.FIRST_NAME} ${currentPatient.LAST_NAME}`;
        }
        
        return currentPatient;
    } catch (error) {
        console.error('Error loading patient data:', error);
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            errorAlert.textContent = `Error: ${error.message}`;
            errorAlert.style.display = 'block';
        }
        
        const rawApiData = document.getElementById('rawApiData');
        if (rawApiData) {
            rawApiData.textContent += `Error loading patient data: ${error.message}\n`;
        }
        
        throw error;
    }
}

// Load billing summary data
async function loadBillingSummary() {
    try {
        // Hide the billing summary section initially
        if (billingSummary) billingSummary.style.display = 'none';
        
        // Make no billing data message visible by default
        const noBillingData = document.getElementById('noBillingData');
        if (noBillingData) noBillingData.style.display = 'block';
        
        // Display debug info
        const rawApiData = document.getElementById('rawApiData');
        if (rawApiData) {
            rawApiData.textContent += `Loading billing summary for patient ID: ${currentPatient.PATIENT_ID}...\n`;
        }
        
        const response = await fetch(`/api/patients/${currentPatient.PATIENT_ID}/billing/summary`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch billing summary');
        }
        
        const data = await response.json();
        
        // Display raw API response
        if (rawApiData) {
            rawApiData.textContent += `Billing Summary Response:\n${JSON.stringify(data, null, 2)}\n\n`;
        }
        
        // Only update UI if we actually received data with valid values
        if (data && 
            (data.outstandingBalance !== undefined || 
             data.lastPayment || 
             data.totalPaid !== undefined)) {
            
            // Hide no billing data message
            if (noBillingData) noBillingData.style.display = 'none';
            
            // Update UI with the data
            updateBillingSummary(data);
            
            // Show the billing summary section
            if (billingSummary) billingSummary.style.display = 'flex';
            
            return data;
        } else {
            // If we got an empty response, keep no billing data message visible
            console.log('No billing data received');
            if (rawApiData) {
                rawApiData.textContent += 'No billing data available for this patient\n';
            }
            return null;
        }
    } catch (error) {
        console.error('Error loading billing summary:', error);
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            errorAlert.textContent = `Error: ${error.message}`;
            errorAlert.style.display = 'block';
        }
        
        const rawApiData = document.getElementById('rawApiData');
        if (rawApiData) {
            rawApiData.textContent += `Error loading billing summary: ${error.message}\n`;
        }
        
        // Don't show the billing summary section if there was an error
        if (billingSummary) billingSummary.style.display = 'none';
        
        throw error;
    } finally {
        const loadingSpinner = document.getElementById('loadingSpinner');
        if (loadingSpinner) loadingSpinner.style.display = 'none';
    }
}

// Update billing summary UI with data
function updateBillingSummary(data) {
    // Only update if we have data
    if (!data) return;

    // Clear all values first to avoid displaying stale data
    outstandingBalance.textContent = '';
    modalOutstandingBalance.textContent = '';
    lastPaymentAmount.textContent = '';
    lastPaymentDate.textContent = '';
    totalPaidAmount.textContent = '';
    yearLabel.textContent = '';
    paymentCount.textContent = '';
    
    // Hide pay button by default
    const payNowBtn = document.getElementById('payNowBtn');
    if (payNowBtn) payNowBtn.style.display = 'none';

    // Update outstanding balance if it exists and is a valid number
    if (data.outstandingBalance !== undefined && data.outstandingBalance !== null) {
        outstandingBalance.textContent = formatCurrency(data.outstandingBalance);
        modalOutstandingBalance.textContent = formatCurrency(data.outstandingBalance);
        
        // Set payment amount field default value
        if (paymentAmount) {
            paymentAmount.value = data.outstandingBalance;
            paymentAmount.max = data.outstandingBalance;
        }
        
        // Show/hide pay now button based on balance
        if (data.outstandingBalance > 0) {
            if (payNowBtn) payNowBtn.style.display = 'block';
        }
    }
    
    // Update last payment info only if we have a payment with valid data
    if (data.lastPayment && data.lastPayment.amount !== undefined && data.lastPayment.date) {
        lastPaymentAmount.textContent = formatCurrency(data.lastPayment.amount);
        lastPaymentDate.textContent = formatDate(data.lastPayment.date);
    } else {
        lastPaymentDate.textContent = 'No payments yet';
    }
    
    // Update total paid info only if we have valid data
    if (data.totalPaid !== undefined && data.totalPaid !== null) {
        const currentYear = new Date().getFullYear();
        yearLabel.textContent = `(${currentYear})`;
        totalPaidAmount.textContent = formatCurrency(data.totalPaid);
        
        // Only show payment count if we have actual payments
        if (data.paymentsCount !== undefined && data.paymentsCount !== null && data.paymentsCount > 0) {
            paymentCount.textContent = data.paymentsCount;
        }
    }
    
    // Store payments list for later use
    paymentsList = (data.payments && Array.isArray(data.payments)) ? data.payments : [];
}

// Load bills
async function loadBills(filter) {
    try {
        // Display debug info
        const rawApiData = document.getElementById('rawApiData');
        if (rawApiData) {
            rawApiData.textContent += `Loading bills for patient ID: ${currentPatient.PATIENT_ID} with filter: ${filter}...\n`;
        }
        
        // Show loading spinner
        showBillsLoading(true);
        
        let endpoint = `/api/patients/${currentPatient.PATIENT_ID}/billing/bills`;
        
        // Add filter parameter if needed
        if (filter && filter !== 'all') {
            endpoint += `?filter=${filter}`;
        }
        
        const response = await fetch(endpoint, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch bills');
        }
        
        const data = await response.json();
        
        // Display raw API response
        if (rawApiData) {
            rawApiData.textContent += `Bills Response:\n${JSON.stringify(data, null, 2)}\n\n`;
        }
        
        // Only proceed if we got a valid array
        if (Array.isArray(data) && data.length > 0) {
            billsList = data;
            renderBills(data);
        } else {
            showNoBillsMessage();
        }
    } catch (error) {
        console.error('Error loading bills:', error);
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            errorAlert.textContent = `Error: ${error.message}`;
            errorAlert.style.display = 'block';
        }
        
        const rawApiData = document.getElementById('rawApiData');
        if (rawApiData) {
            rawApiData.textContent += `Error loading bills: ${error.message}\n`;
        }
        
        showBillsError('Failed to load bills. Please try again later.');
    } finally {
        showBillsLoading(false);
    }
}

// Render bills
function renderBills(bills) {
    // Clear current bills list
    billsList_el.innerHTML = '';
    
    // Check if there are any bills
    if (!bills || !Array.isArray(bills) || bills.length === 0) {
        noBillsMessage.style.display = 'block';
        billsList_el.style.display = 'none';
        return;
    }
    
    // Hide no bills message
    noBillsMessage.style.display = 'none';
    billsList_el.style.display = 'block';
    
    // Sort bills by date (newest first)
    bills.sort((a, b) => new Date(b.date) - new Date(a.date));
    
    // Render each bill
    bills.forEach(bill => {
        // Only create list items for bills with valid data
        if (bill && bill.id) {
            const billItem = createBillListItem(bill);
            billsList_el.appendChild(billItem);
        }
    });
}

// Create bill list item
function createBillListItem(bill) {
    const listItem = document.createElement('li');
    listItem.className = 'list-group-item p-3';
    
    // Make sure we have a valid bill object
    if (!bill) return listItem;
    
    // Format date if it exists
    const billDate = bill.date ? formatDate(bill.date) : '';
    
    // Create HTML for the bill item, only showing data that exists
    listItem.innerHTML = `
        <div class="row align-items-center">
            <div class="col-md-6">
                ${bill.serviceName ? `<h6 class="mb-1">${bill.serviceName}</h6>` : ''}
                ${bill.doctorName ? `<p class="mb-0 text-muted">${bill.doctorName}</p>` : ''}
                ${billDate ? `<small class="text-muted">${billDate}</small>` : ''}
            </div>
            <div class="col-md-3 text-md-end">
                ${bill.amount !== undefined ? `<h5 class="mb-0">${formatCurrency(bill.amount)}</h5>` : ''}
                ${bill.status ? `<span class="badge ${getBillStatusClass(bill.status)}">${bill.status}</span>` : ''}
            </div>
            <div class="col-md-3 text-md-end">
                <button class="btn btn-sm btn-outline-primary view-bill" data-id="${bill.id}">
                    <i class="fas fa-eye me-1"></i> View
                </button>
                ${bill.status === 'Pending' ? `
                <button class="btn btn-sm btn-primary pay-bill" data-id="${bill.id}" data-amount="${bill.amount || 0}">
                    <i class="fas fa-credit-card me-1"></i> Pay
                </button>
                ` : ''}
            </div>
        </div>
    `;
    
    // Add event listeners to buttons
    setTimeout(() => {
        const viewBtn = listItem.querySelector('.view-bill');
        if (viewBtn) {
            viewBtn.addEventListener('click', () => viewBillDetails(bill.id));
        }
        
        const payBtn = listItem.querySelector('.pay-bill');
        if (payBtn) {
            payBtn.addEventListener('click', () => {
                // Set payment amount to bill amount only if it exists
                if (bill.amount !== undefined) {
                    paymentAmount.value = bill.amount;
                    paymentAmount.max = bill.amount;
                    modalOutstandingBalance.textContent = formatCurrency(bill.amount);
                }
                
                // Show payment modal
                const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
                paymentModal.show();
            });
        }
    }, 0);
    
    return listItem;
}

// View bill details
async function viewBillDetails(billId) {
    try {
        // Validate bill ID
        if (!billId) {
            throw new Error('Invalid bill ID');
        }
        
        // Find bill in the list
        let bill = billsList.find(b => b.id.toString() === billId.toString());
        
        // If not found in the list, fetch it from the API
        if (!bill) {
            // Show loading toast
            showToast('info', 'Loading bill details...');
            
            // Fetch bill details
            const response = await fetch(`/api/bills/${billId}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load bill details');
            }
            
            bill = await response.json();
            
            // Validate that we received actual data
            if (!bill) {
                throw new Error('No bill details received');
            }
        }
        
        // Clear previous data first
        clearBillDetailModal();
        
        // Update modal elements only if corresponding data exists
        if (bill.serviceName) {
            document.getElementById('billDetailTitle').textContent = bill.serviceName;
        } else {
            document.getElementById('billDetailTitle').textContent = 'Bill Details';
        }
        
        if (bill.id) {
            document.getElementById('billDetailId').textContent = bill.id;
        }
        
        if (bill.date) {
            document.getElementById('billDetailDate').textContent = formatDate(bill.date);
        }
        
        if (bill.status) {
            const statusBadge = document.getElementById('billDetailStatus');
            statusBadge.textContent = bill.status;
            statusBadge.className = `badge ${getBillStatusClass(bill.status)}`;
        }
        
        if (bill.doctorName) {
            document.getElementById('billDetailDoctor').textContent = bill.doctorName;
        }
        
        if (bill.department) {
            document.getElementById('billDetailDepartment').textContent = bill.department;
        }
        
        // Load bill items if they exist
        const billItems = bill.items && Array.isArray(bill.items) ? bill.items : [];
        const billItemsContainer = document.getElementById('billDetailItems');
        billItemsContainer.innerHTML = '';
        
        if (billItems.length === 0) {
            // No items, add a single row with available data
            billItemsContainer.innerHTML = `
                <tr>
                    <td>${bill.serviceName || '-'}</td>
                    <td>${bill.serviceCode || '-'}</td>
                    <td class="text-end">${bill.amount !== undefined ? formatCurrency(bill.amount) : '-'}</td>
                </tr>
            `;
        } else {
            // Add each item
            billItems.forEach(item => {
                if (item) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.description || '-'}</td>
                        <td>${item.code || '-'}</td>
                        <td class="text-end">${item.amount !== undefined ? formatCurrency(item.amount) : '-'}</td>
                    `;
                    billItemsContainer.appendChild(row);
                }
            });
        }
        
        // Update totals only if they exist
        if (bill.subtotal !== undefined || bill.amount !== undefined) {
            document.getElementById('billDetailSubtotal').textContent = formatCurrency(bill.subtotal || bill.amount);
        }
        
        if (bill.insuranceCoverage !== undefined) {
            document.getElementById('billDetailInsurance').textContent = formatCurrency(bill.insuranceCoverage);
        }
        
        if (bill.patientResponsibility !== undefined || bill.amount !== undefined) {
            document.getElementById('billDetailTotal').textContent = formatCurrency(bill.patientResponsibility || bill.amount);
        }
        
        // Set footer buttons based on bill status
        const footerContainer = document.getElementById('billDetailFooter');
        footerContainer.innerHTML = '';
        
        // Always add close button
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn btn-secondary';
        closeBtn.setAttribute('data-bs-dismiss', 'modal');
        closeBtn.textContent = 'Close';
        footerContainer.appendChild(closeBtn);
        
        // Add action buttons based on status
        if (bill.status === 'Pending') {
            const payBtn = document.createElement('button');
            payBtn.type = 'button';
            payBtn.className = 'btn btn-primary pay-modal-bill';
            payBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay Now';
            payBtn.setAttribute('data-id', bill.id);
            if (bill.amount !== undefined) {
                payBtn.setAttribute('data-amount', bill.amount);
            }
            footerContainer.appendChild(payBtn);
            
            // Add event listener to pay button
            setTimeout(() => {
                const payBtn = footerContainer.querySelector('.pay-modal-bill');
                if (payBtn) {
                    payBtn.addEventListener('click', () => {
                        // Hide current modal
                        const billDetailModal = bootstrap.Modal.getInstance(document.getElementById('billDetailModal'));
                        billDetailModal.hide();
                        
                        // Set payment amount to bill amount
                        if (bill.patientResponsibility !== undefined || bill.amount !== undefined) {
                            paymentAmount.value = bill.patientResponsibility || bill.amount;
                            paymentAmount.max = bill.patientResponsibility || bill.amount;
                            modalOutstandingBalance.textContent = formatCurrency(bill.patientResponsibility || bill.amount);
                        }
                        
                        // Show payment modal
                        const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
                        paymentModal.show();
                    });
                }
            }, 0);
        } else if (bill.status === 'Paid') {
            const downloadBtn = document.createElement('button');
            downloadBtn.type = 'button';
            downloadBtn.className = 'btn btn-outline-primary';
            downloadBtn.id = 'downloadBillBtn';
            downloadBtn.innerHTML = '<i class="fas fa-download me-2"></i>Download';
            downloadBtn.setAttribute('data-id', bill.id);
            footerContainer.appendChild(downloadBtn);
            
            // Add event listener to download button
            setTimeout(() => {
                const downloadBtn = document.getElementById('downloadBillBtn');
                if (downloadBtn) {
                    downloadBtn.addEventListener('click', () => {
                        downloadBill(bill.id);
                    });
                }
            }, 0);
        }
        
        // Show the modal
        const billDetailModal = new bootstrap.Modal(document.getElementById('billDetailModal'));
        billDetailModal.show();
    } catch (error) {
        console.error('Error viewing bill details:', error);
        showToast('error', 'Failed to load bill details');
    }
}

// Clear bill detail modal values
function clearBillDetailModal() {
    document.getElementById('billDetailTitle').textContent = 'Bill Details';
    document.getElementById('billDetailId').textContent = '-';
    document.getElementById('billDetailDate').textContent = '-';
    
    const statusBadge = document.getElementById('billDetailStatus');
    statusBadge.textContent = '-';
    statusBadge.className = 'badge';
    
    document.getElementById('billDetailDoctor').textContent = '-';
    document.getElementById('billDetailDepartment').textContent = '-';
    document.getElementById('billDetailItems').innerHTML = '';
    document.getElementById('billDetailSubtotal').textContent = '-';
    document.getElementById('billDetailInsurance').textContent = '-';
    document.getElementById('billDetailTotal').textContent = '-';
}

// Process payment
async function processPayment() {
    try {
        // Validate form
        if (!paymentForm.checkValidity()) {
            paymentForm.classList.add('was-validated');
            return;
        }
        
        // Get form data
        const amount = parseFloat(paymentAmount.value);
        const method = paymentMethod.value;
        
        // Validate payment amount
        if (!amount || isNaN(amount) || amount <= 0) {
            showToast('error', 'Please enter a valid payment amount');
            return;
        }
        
        // Validate amount against outstanding balance if it exists
        const outstandingAmountText = modalOutstandingBalance.textContent;
        if (outstandingAmountText) {
            const outstandingAmount = parseFloat(outstandingAmountText.replace(/[^0-9.-]+/g, ''));
            
            if (amount > outstandingAmount) {
                showToast('error', 'Payment amount cannot exceed outstanding balance');
                return;
            }
        }
        
        // Show processing toast
        showToast('info', 'Processing payment...');
        
        // Get patient ID from localStorage
        const patientId = localStorage.getItem('userId');
        
        // Prepare data for API
        const paymentData = {
            patientId,
            amount,
            method,
            date: new Date().toISOString()
        };
        
        // Make API request to process payment
        const response = await fetch('/api/payments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify(paymentData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to process payment');
        }
        
        // Get payment result
        const result = await response.json();
        
        // Reset form and hide modal
        paymentForm.reset();
        paymentForm.classList.remove('was-validated');
        const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
        modal.hide();
        
        // Show success toast
        showToast('success', 'Payment processed successfully');
        
        // Reload billing data
        await loadBillingSummary();
        await loadBills(selectedBillTimeframe);
    } catch (error) {
        console.error('Error processing payment:', error);
        showToast('error', 'Failed to process payment. Please try again later.');
    }
}

// Download bill
function downloadBill(billId) {
    // In a real app, this would generate a PDF or redirect to a download endpoint
    showToast('info', 'Bill download would be implemented here');
}

// Helper function to get CSS class for bill status
function getBillStatusClass(status) {
    if (!status) return 'bg-secondary';
    
    switch (status) {
        case 'Paid':
            return 'bg-success';
        case 'Pending':
            return 'bg-warning';
        case 'Overdue':
            return 'bg-danger';
        case 'Processing':
            return 'bg-info';
        case 'Insurance Review':
            return 'bg-primary';
        default:
            return 'bg-secondary';
    }
}

// Format currency
function formatCurrency(amount) {
    if (amount === undefined || amount === null) return '';
    
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
    } catch (e) {
        return '';
    }
}

// Show/hide loading spinner
function showLoading(isLoading) {
    loadingSpinner.style.display = isLoading ? 'flex' : 'none';
    
    // Hide other sections when loading
    if (isLoading) {
        const noBillingData = document.getElementById('noBillingData');
        if (noBillingData) noBillingData.style.display = 'none';
        billingSummarySection.style.display = 'none';
        recentBillsSection.style.display = 'none';
    }
}

// Show/hide bills loading spinner
function showBillsLoading(isLoading) {
    billsLoadingSpinner.style.display = isLoading ? 'block' : 'none';
    billsList_el.style.display = isLoading ? 'none' : 'block';
}

// Show bills error message
function showBillsError(message) {
    billsErrorMessage.textContent = message;
    billsErrorMessage.style.display = 'block';
    noBillsMessage.style.display = 'none';
}

// Show error message
function showError(message) {
    errorAlert.textContent = message;
    errorAlert.style.display = 'block';
}

// Show toast notification
function showToast(type, message) {
    const toastId = 'toast-' + Date.now();
    const toastEl = document.createElement('div');
    toastEl.className = 'toast';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.id = toastId;
    
    const icon = type === 'success' ? 'fa-check-circle' : 
                type === 'error' ? 'fa-exclamation-circle' : 
                'fa-info-circle';
    
    const colorClass = type === 'success' ? 'text-success' : 
                       type === 'error' ? 'text-danger' : 
                       'text-info';
    
    toastEl.innerHTML = `
        <div class="toast-header">
            <i class="fas ${icon} ${colorClass} me-2"></i>
            <strong class="me-auto">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Information'}</strong>
            <small>just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;
    
    toastContainer.appendChild(toastEl);
    
    const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: 5000
    });
    
    toast.show();
    
    // Remove toast after it's hidden
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

// Logout function
function logout() {
    // Clear localStorage
    localStorage.removeItem('authToken');
    localStorage.removeItem('userId');
    localStorage.removeItem('userType');
    
    // Redirect to login page
    window.location.href = '../../login/index.html';
} 