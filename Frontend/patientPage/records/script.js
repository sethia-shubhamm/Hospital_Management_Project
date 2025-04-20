const sliderButtons = document.querySelectorAll('.slider button');

sliderButtons.forEach(button => {
    button.addEventListener('click', () => {
        
        sliderButtons.forEach(btn => btn.classList.remove('active'));
        
        button.classList.add('active');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    
    setupLogoutButton();
    
    
    loadUserData()
        .then(userData => {
            if (userData) {
                
                updateWelcomeMessage(userData.name);
                
                
                fetchMedicalRecords(userData.user_id);
            }
        })
        .catch(error => {
            console.error('Error in initial data loading:', error);
            showErrorMessage('Failed to load user data. Please log in again.');
        });
});

/**
 * Load user data from localStorage
 * @returns {Promise<Object|null>} User data or null if not found
 */
function loadUserData() {
    return new Promise((resolve, reject) => {
        try {
            
            const userDataString = localStorage.getItem('userData');
            
            if (!userDataString) {
                console.error('No user data found in localStorage');
                window.location.href = '/login/index.html';
                resolve(null);
                return;
            }
            
            const userData = JSON.parse(userDataString);
            console.log('Loaded user data:', userData);
            
            
            if (userData.user_type !== 'patient') {
                console.error('Non-patient user trying to access patient records');
                window.location.href = '/login/index.html';
                resolve(null);
                return;
            }
            
            resolve(userData);
        } catch (error) {
            console.error('Error loading user data:', error);
            window.location.href = '/login/index.html';
            reject(error);
        }
    });
}

/**
 * Update welcome message with patient name
 * @param {string} patientName - Patient's name
 */
function updateWelcomeMessage(patientName) {
    const welcomeHeading = document.querySelector('.welcome-section h1');
    
    if (welcomeHeading && patientName) {
        welcomeHeading.textContent = `Welcome, ${patientName}`;
    }
}

/**
 * Fetch medical records for a patient
 * @param {string} patientId - Patient ID
 */
function fetchMedicalRecords(patientId) {
    showLoadingState();
    
    fetch(`/api/patient/records/${patientId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to fetch records: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoadingState();
            console.log('API response:', data); 
            
            if (data.success) {
                updatePatientInfo(data.patientInfo);
                displayMedicalRecords(data.medicalRecords);
                displayRecentVisits(data.recentVisits);
            } else {
                console.error('API returned error:', data.message);
                showErrorMessage('Could not load medical records: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error fetching medical records:', error);
            hideLoadingState();
            showErrorMessage('Could not connect to the server. Please check your connection and try again.');
        });
}

/**
 * Update patient information in the overview section
 * @param {Object} patientInfo - Patient information object
 */
function updatePatientInfo(patientInfo) {
    if (!patientInfo) return;
    
    console.log('Received patient info:', patientInfo); 
    
    const patientInfoContainer = document.querySelector('.patient-info');
    
    if (patientInfoContainer) {
        
        const infoItems = patientInfoContainer.querySelectorAll('.info-item');
        
        
        infoItems.forEach(item => {
            const label = item.querySelector('span').textContent.trim().toLowerCase();
            const valueElement = item.querySelector('p');
            
            if (!valueElement) return;
            
            
            if (label.includes('patient id') && patientInfo.patientId) {
                valueElement.textContent = patientInfo.patientId;
                valueElement.id = 'patientId';
            } 
            else if (label.includes('blood group') && patientInfo.bloodType) {
                valueElement.textContent = patientInfo.bloodType;
                valueElement.id = 'bloodGroup';
            } 
            else if (label.includes('age') && patientInfo.age) {
                valueElement.textContent = `${patientInfo.age} Years`;
                valueElement.id = 'age';
            }
        });
        
        
        const nameElement = patientInfoContainer.querySelector('h3');
        if (nameElement && patientInfo.name) {
            nameElement.textContent = patientInfo.name;
            nameElement.classList.add('name');
        }
    }
}

/**
 * Display medical records in the records section
 * @param {Array} records - Array of medical record objects
 */
function displayMedicalRecords(records) {
    const recordsContainer = document.querySelector('.records-grid');
    
    if (!recordsContainer) return;
    
    
    recordsContainer.innerHTML = '';
    
    if (!records || records.length === 0) {
        recordsContainer.innerHTML = '<div class="no-records">No medical records found.</div>';
        return;
    }
    
    
    records.forEach(record => {
        const recordCard = document.createElement('div');
        recordCard.className = 'record-card';
        
        
        const recordDate = new Date(record.DATE || record.DATE_CREATED);
        const formattedDate = recordDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        
        const doctorName = record.doctor_name || 'Unknown';
        const diagnosis = record.DIAGNOSIS || 'General Checkup';
        const symptoms = record.SYMPTOMS || 'Not recorded';
        const prescription = record.PRESCRIPTION || record.PRESCRIPTIONS || 'None';
        const notes = record.NOTES || 'No additional notes';
        const recordId = record.RECORD_ID || record.REC_ID || '';
        const specialty = record.SPECIALITY || 'General Medicine';
        
        recordCard.innerHTML = `
            <div class="record-header">
                <h3>${diagnosis}</h3>
                <span class="date">${formattedDate}</span>
            </div>
            <div class="record-body">
                <p><strong>Doctor:</strong> Dr. ${doctorName}</p>
                <p><strong>Specialty:</strong> ${specialty}</p>
                <p><strong>Symptoms:</strong> ${symptoms}</p>
                <p><strong>Diagnosis:</strong> ${diagnosis}</p>
                <p><strong>Prescription:</strong> ${prescription}</p>
                <p><strong>Notes:</strong> ${notes}</p>
            </div>
            <div class="record-footer">
                <span>Record ID: ${recordId}</span>
            </div>
        `;
        
        recordsContainer.appendChild(recordCard);
    });
}

/**
 * Display recent visits in the sidebar
 * @param {Array} visits - Array of recent visit objects
 */
function displayRecentVisits(visits) {
    const recentVisitsContainer = document.querySelector('.recent-visits');
    
    
    if (!recentVisitsContainer) {
        const recordsContainer = document.querySelector('.records-grid');
        if (!recordsContainer) return;
        
        const newContainer = document.createElement('div');
        newContainer.className = 'record-card';
        newContainer.innerHTML = `
            <div class="record-header">
                <h3>Recent Visits</h3>
            </div>
            <div class="recent-visits"></div>
        `;
        recordsContainer.appendChild(newContainer);
    }
    
    
    const visitsContainer = document.querySelector('.recent-visits');
    if (!visitsContainer) return;
    
    
    let visitsList = visitsContainer.querySelector('ul');
    
    if (!visitsList) {
        visitsList = document.createElement('ul');
        visitsContainer.appendChild(visitsList);
    } else {
        
        visitsList.innerHTML = '';
    }
    
    if (!visits || visits.length === 0) {
        visitsList.innerHTML = '<li class="no-visits">No recent visits found.</li>';
        return;
    }
    
    
    visits.forEach(visit => {
        
        const visitDate = new Date(visit.DATE || visit.APPOINTMENT_DATE);
        const formattedDate = visitDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        
        
        const doctorName = visit.doctor_name || 'Unknown';
        const visitType = visit.SPECIALITY || visit.APPOINTMENT_TYPE || 'Checkup';
        const visitTime = visit.TIME || '';
        
        const listItem = document.createElement('li');
        listItem.innerHTML = `
            <div class="visit-info">
                <span class="visit-date">${formattedDate}</span>
                <span class="visit-doctor">Dr. ${doctorName}</span>
                <span class="visit-type">${visitType}</span>
                ${visitTime ? `<span class="visit-time">${visitTime}</span>` : ''}
            </div>
        `;
        
        visitsList.appendChild(listItem);
    });
}

/**
 * Show loading state
 */
function showLoadingState() {
    
    const recordsContainer = document.querySelector('.records-grid');
    const recentVisitsContainer = document.querySelector('.recent-visits');
    const patientInfoContainer = document.querySelector('.patient-info');
    
    
    if (recordsContainer) {
        recordsContainer.innerHTML = '<div class="loading">Loading medical records...</div>';
    }
    
    if (recentVisitsContainer) {
        const visitsList = recentVisitsContainer.querySelector('ul') || document.createElement('ul');
        visitsList.innerHTML = '<li class="loading">Loading recent visits...</li>';
        
        if (!recentVisitsContainer.contains(visitsList)) {
            recentVisitsContainer.appendChild(visitsList);
        }
    }
    
    if (patientInfoContainer) {
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading';
        loadingDiv.textContent = 'Loading patient information...';
        
        
        if (!patientInfoContainer.dataset.originalContent) {
            patientInfoContainer.dataset.originalContent = patientInfoContainer.innerHTML;
        }
        
        patientInfoContainer.innerHTML = '';
        patientInfoContainer.appendChild(loadingDiv);
    }
}

/**
 * Hide loading state
 */
function hideLoadingState() {
    
    const loadingElements = document.querySelectorAll('.loading');
    
    loadingElements.forEach(element => {
        element.remove();
    });
    
    
    const patientInfoContainer = document.querySelector('.patient-info');
    
    if (patientInfoContainer && patientInfoContainer.dataset.originalContent && 
        !patientInfoContainer.querySelector(':not(.loading)')) {
        patientInfoContainer.innerHTML = patientInfoContainer.dataset.originalContent;
        delete patientInfoContainer.dataset.originalContent;
    }
}

/**
 * Show error message
 * @param {string} message - Error message
 */
function showErrorMessage(message) {
    
    const existingError = document.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    const container = document.querySelector('.records-container');
    
    if (container) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        container.prepend(errorDiv);
        
        
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
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