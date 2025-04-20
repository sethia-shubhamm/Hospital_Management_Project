document.addEventListener('DOMContentLoaded', function() {
    
    setupLogoutButton();
    
    
    loadUserData();
});

/**
 * Load user data from localStorage and fetch profile data
 */
function loadUserData() {
    
    const userData = JSON.parse(localStorage.getItem('userData'));
    
    if (!userData || !userData.user_id) {
        showErrorMessage('User data not found. Please log in again.');
        return;
    }
    
    
    showLoadingState();
    
    
    fetchProfileData(userData.user_id);
}

/**
 * Fetch profile data from the API
 * @param {string|number} patientId - The ID of the patient
 */
function fetchProfileData(patientId) {
    fetch(`/api/patient/profile/${patientId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch profile data');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.patient) {
                populateProfileData(data.patient);
            } else {
                throw new Error(data.message || 'Failed to fetch profile data');
            }
        })
        .catch(error => {
            console.error('Error fetching profile data:', error);
            showErrorMessage('Failed to load profile data. Please try again later.');
        })
        .finally(() => {
            hideLoadingState();
        });
}

/**
 * Show loading state
 */
function showLoadingState() {
    const containers = document.querySelectorAll('.personal-info, .account-settings');
    
    containers.forEach(container => {
        const form = container.querySelector('.info-form, .settings-form');
        if (form) {
            form.innerHTML = `
                <div class="loading-message">
                    <p>Loading profile data...</p>
                </div>
            `;
        }
    });
}

/**
 * Hide loading state
 */
function hideLoadingState() {
    
}

/**
 * Show error message
 * @param {string} message - The error message to display
 */
function showErrorMessage(message) {
    const containers = document.querySelectorAll('.personal-info, .account-settings');
    
    containers.forEach(container => {
        const form = container.querySelector('.info-form, .settings-form');
        if (form) {
            form.innerHTML = `
                <div class="error-message">
                    <p>${message}</p>
                </div>
            `;
        }
    });
}

/**
 * Populate profile data with values from the database
 * @param {Object} patient - The patient data object from the API
 */
function populateProfileData(patient) {
    
    const profileName = document.querySelector('.profile-info h2');
    const profileId = document.querySelector('.profile-info p');
    
    if (profileName && patient.NAME) {
        profileName.textContent = patient.NAME;
    }
    
    if (profileId && patient.PATIENT_ID) {
        profileId.textContent = `Patient ID: ${patient.PATIENT_ID}`;
    }
    
    
    
    const registrationDate = new Date();
    const formattedRegDate = `${registrationDate.toLocaleString('default', { month: 'long' })} ${registrationDate.getFullYear()}`;
    
    
    const personalInfoContainer = document.querySelector('.personal-info');
    if (personalInfoContainer) {
        const infoForm = personalInfoContainer.querySelector('.info-form');
        
        if (infoForm) {
            infoForm.innerHTML = `
                <div class="info-message">
                    Personal information from the database
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="${patient.NAME || 'Not available'}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Age</label>
                        <input type="text" value="${patient.AGE || 'Not available'}" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Gender</label>
                        <input type="text" value="${patient.GENDER || 'Not available'}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Blood Group</label>
                        <input type="text" value="${patient.BLOOD_TYPE || 'Not available'}" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" value="${patient.CONTACT || 'Not available'}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="${patient.EMAIL || 'Not available'}" readonly>
                    </div>
                </div>
            `;
        }
    }
    
    
    const accountSettingsContainer = document.querySelector('.account-settings');
    if (accountSettingsContainer) {
        const settingsForm = accountSettingsContainer.querySelector('.settings-form');
        
        if (settingsForm) {
            settingsForm.innerHTML = `
                <div class="info-message">
                    Account information from the database
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="${patient.EMAIL || 'Not available'}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Account Type</label>
                        <input type="text" value="Patient" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label>Account Status</label>
                    <input type="text" value="Active" readonly>
                </div>
                <div class="form-group">
                    <label>Registration Date</label>
                    <input type="text" value="${formattedRegDate}" readonly>
                </div>
            `;
        }
    }
}

/**
 * Setup logout button functionality
 */
function setupLogoutButton() {
    const logoutButton = document.querySelector('.logout');
    if (logoutButton) {
        logoutButton.addEventListener('click', function() {
            
            localStorage.removeItem('userData');
            
            window.location.href = '/login/index.html';
        });
    }
}



