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
                
                fetchDoctors();
                
                
                setupSearchAndFilters();
                
                
                window.patientId = userData.user_id;
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
                console.error('Non-patient user trying to access patient appointments');
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
 * Fetch all available doctors
 */
function fetchDoctors() {
    showLoadingState();
    
    fetch('/api/patient/available-doctors')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to fetch doctors: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            hideLoadingState();
            console.log('Doctors API response:', data); 
            
            if (data.success) {
                const doctors = data.doctors || [];
                
                if (doctors.length > 0) {
                    displayDoctors(doctors);
                    
                    populateSpecialtyFilter(doctors);
                } else {
                    const doctorsGrid = document.querySelector('.doctors-grid');
                    if (doctorsGrid) {
                        doctorsGrid.innerHTML = '<div class="no-results">No doctors available at this time. Please try again later.</div>';
                    }
                }
            } else {
                console.error('API returned error:', data.message);
                showErrorMessage('Could not load doctors: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error fetching doctors:', error);
            hideLoadingState();
            showErrorMessage('Could not connect to the server. Please check your connection and try again.');
        });
}

/**
 * Display doctors in the grid
 * @param {Array} doctors - Array of doctor objects
 */
function displayDoctors(doctors) {
    const doctorsGrid = document.querySelector('.doctors-grid');
    
    if (!doctorsGrid) return;
    
    
    doctorsGrid.innerHTML = '';
    
    if (doctors.length === 0) {
        doctorsGrid.innerHTML = '<div class="no-results">No doctors found. Please try different search criteria.</div>';
        return;
    }
    
    
    doctors.forEach(doctor => {
        
        const doctorId = doctor.DOCTOR_ID;
        const doctorName = doctor.NAME || 'Unknown';
        const specialty = doctor.SPECIALITY || 'General Medicine';
        
        const doctorCard = document.createElement('div');
        doctorCard.className = 'doctor-card';
        doctorCard.innerHTML = `
            <img src="${doctor.IMAGE_URL || 'icons/doctor.png'}" alt="Doctor">
            <div class="doctor-info">
                <h3>Dr. ${doctorName}</h3>
                <p class="specialty">${specialty}</p>
                <p class="experience">ID: ${doctorId}</p>
                <div class="availability">
                    <span class="available-tag">${doctor.AVAILABILITY || 'Available Today'}</span>
                    <span class="time">${doctor.WORKING_HOURS || '09:00 AM - 05:00 PM'}</span>
                </div>
                <button class="book-btn" data-doctor-id="${doctorId}" data-doctor-name="${doctorName}">Book Appointment</button>
            </div>
        `;
        
        doctorsGrid.appendChild(doctorCard);
    });
    
    
    const bookButtons = document.querySelectorAll('.book-btn');
    bookButtons.forEach(button => {
        button.addEventListener('click', () => {
            const doctorId = button.getAttribute('data-doctor-id');
            const doctorName = button.getAttribute('data-doctor-name');
            showBookingModal(doctorId, doctorName);
        });
    });
}

/**
 * Show booking modal for a doctor
 * @param {string} doctorId - Doctor ID
 * @param {string} doctorName - Doctor name
 */
function showBookingModal(doctorId, doctorName) {
    
    const existingModal = document.querySelector('.booking-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    
    const modal = document.createElement('div');
    modal.className = 'booking-modal';
    
    
    const today = new Date().toISOString().split('T')[0];
    
    
    const startHour = 9; 
    const endHour = 17;  
    const interval = 60; 
    
    const timeSlots = [];
    for (let hour = startHour; hour < endHour; hour++) {
        const formattedHour = hour > 12 ? (hour - 12) : hour;
        const amPm = hour >= 12 ? 'PM' : 'AM';
        const paddedHour = formattedHour < 10 ? '0' + formattedHour : formattedHour;
        timeSlots.push(`${paddedHour}:00 ${amPm}`);
    }
    
    const timeSlotOptions = timeSlots.map(time => 
        `<option value="${time}">${time}</option>`
    ).join('');
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>Book Appointment with Dr. ${doctorName}</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="booking-form">
                    <div class="form-group">
                        <label for="appointment-date">Select Date</label>
                        <input type="date" id="appointment-date" min="${today}" required>
                    </div>
                    <div class="form-group">
                        <label for="appointment-time">Select Time</label>
                        <select id="appointment-time" required>
                            <option value="">Select a time slot</option>
                            ${timeSlotOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="appointment-reason">Reason for Visit</label>
                        <select id="appointment-reason" required>
                            <option value="">Select reason</option>
                            <option value="Consultation">General Consultation</option>
                            <option value="Follow-up">Follow-up Visit</option>
                            <option value="Test Results">Discuss Test Results</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="confirm-btn">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    
    document.body.appendChild(modal);
    
    
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    
    const closeBtn = modal.querySelector('.close-btn');
    closeBtn.addEventListener('click', () => {
        closeModal(modal);
    });
    
    
    const cancelBtn = modal.querySelector('.cancel-btn');
    cancelBtn.addEventListener('click', () => {
        closeModal(modal);
    });
    
    
    const bookingForm = document.getElementById('booking-form');
    bookingForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const date = document.getElementById('appointment-date').value;
        const time = document.getElementById('appointment-time').value;
        const reason = document.getElementById('appointment-reason').value;
        
        bookAppointment(doctorId, date, time, reason, modal);
    });
}

/**
 * Close the booking modal
 * @param {Element} modal - Modal element
 */
function closeModal(modal) {
    modal.classList.remove('show');
    setTimeout(() => {
        modal.remove();
    }, 300);
}

/**
 * Book an appointment with a doctor
 * @param {string} doctorId - Doctor ID
 * @param {string} date - Appointment date
 * @param {string} time - Appointment time
 * @param {string} reason - Reason for visit
 * @param {Element} modal - Modal element to close after booking
 */
function bookAppointment(doctorId, date, time, reason, modal) {
    const patientId = window.patientId;
    
    if (!patientId) {
        showErrorMessage('Patient ID is missing. Please log in again.');
        return;
    }
    
    
    const formActions = modal.querySelector('.form-actions');
    formActions.innerHTML = '<div class="loading">Processing your booking...</div>';
    
    
    const formData = new FormData();
    formData.append('book_appointment', '1');
    formData.append('doctorId', doctorId);
    formData.append('doctorName', ''); 
    formData.append('appointmentDate', date);
    formData.append('appointmentTime', time);
    formData.append('reason', reason);
    
    
    fetch('process_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        closeModal(modal);
        
        if (data.success) {
            showSuccessMessage('Appointment booked successfully!');
            
            
            setTimeout(() => {
                window.location.reload();
                
                
            }, 2000);
        } else {
            
            if (data.message.includes('already has an appointment') || 
                data.message.includes('doctor is already booked') ||
                data.message.includes('not available')) {
                
                showErrorMessage('⚠️ Booking conflict: ' + data.message);
            } else {
                showErrorMessage('❌ ' + (data.message || 'Failed to book appointment. Please try again.'));
            }
        }
    })
    .catch(error => {
        console.error('Error booking appointment:', error);
        closeModal(modal);
        showErrorMessage('⚠️ Error connecting to the server. Please try again later.');
    });
}

/**
 * Setup search and filter functionality
 */
function setupSearchAndFilters() {
    const searchInput = document.querySelector('.search-box input');
    const specialtyFilter = document.querySelector('select:nth-of-type(1)');
    const availabilityFilter = document.querySelector('select:nth-of-type(2)');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterDoctors);
    }
    
    if (specialtyFilter) {
        specialtyFilter.addEventListener('change', filterDoctors);
    }
    
    if (availabilityFilter) {
        availabilityFilter.addEventListener('change', filterDoctors);
    }
}

/**
 * Filter doctors based on search and filter inputs
 */
function filterDoctors() {
    const searchInput = document.querySelector('.search-box input');
    const specialtyFilter = document.querySelector('select:nth-of-type(1)');
    
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const specialty = specialtyFilter ? specialtyFilter.value : '';
    
    const doctorCards = document.querySelectorAll('.doctor-card');
    
    doctorCards.forEach(card => {
        const doctorName = card.querySelector('h3').textContent.toLowerCase();
        const doctorSpecialty = card.querySelector('.specialty').textContent.toLowerCase();
        
        
        const matchesSearch = !searchTerm || 
            doctorName.includes(searchTerm) || 
            doctorSpecialty.includes(searchTerm);
        
        
        const matchesSpecialty = !specialty || 
            doctorSpecialty === specialty.toLowerCase();
        
        
        if (matchesSearch && matchesSpecialty) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    
    const visibleCards = Array.from(doctorCards).filter(card => card.style.display !== 'none');
    const noResultsDiv = document.querySelector('.no-results');
    
    if (visibleCards.length === 0) {
        if (!noResultsDiv) {
            const doctorsGrid = document.querySelector('.doctors-grid');
            const newNoResults = document.createElement('div');
            newNoResults.className = 'no-results';
            newNoResults.textContent = 'No doctors found matching your criteria.';
            doctorsGrid.appendChild(newNoResults);
        }
    } else if (noResultsDiv) {
        noResultsDiv.remove();
    }
}

/**
 * Populate specialty filter from doctors data
 * @param {Array} doctors - Array of doctor objects
 */
function populateSpecialtyFilter(doctors) {
    const specialtyFilter = document.querySelector('select:nth-of-type(1)');
    
    if (!specialtyFilter) return;
    
    
    const specialties = [...new Set(doctors.map(doctor => doctor.SPECIALITY))];
    
    
    specialties.sort();
    
    
    specialtyFilter.innerHTML = '<option value="">All Specialties</option>';
    
    
    specialties.forEach(specialty => {
        const option = document.createElement('option');
        option.value = specialty.toLowerCase();
        option.textContent = specialty;
        specialtyFilter.appendChild(option);
    });
}

/**
 * Show loading state
 */
function showLoadingState() {
    const doctorsGrid = document.querySelector('.doctors-grid');
    
    if (doctorsGrid) {
        doctorsGrid.innerHTML = '<div class="loading">Loading doctors...</div>';
    }
}

/**
 * Hide loading state
 */
function hideLoadingState() {
    const loading = document.querySelector('.loading');
    
    if (loading) {
        loading.remove();
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
    
    const container = document.querySelector('.appointment-container');
    
    if (container) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        
        
        if (message.includes('Booking conflict:')) {
            errorDiv.className = 'error-message booking-conflict';
        }
        
        errorDiv.textContent = message;
        
        container.prepend(errorDiv);
        
        
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        
        setTimeout(() => {
            errorDiv.classList.add('fade-out');
            setTimeout(() => {
                errorDiv.remove();
            }, 500);
        }, message.includes('Booking conflict:') ? 8000 : 5000);
    }
}

/**
 * Show success message
 * @param {string} message - Success message
 */
function showSuccessMessage(message) {
    
    const existingSuccess = document.querySelector('.success-message');
    if (existingSuccess) {
        existingSuccess.remove();
    }
    
    const container = document.querySelector('.appointment-container');
    
    if (container) {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.textContent = message;
        
        container.prepend(successDiv);
        
        
        setTimeout(() => {
            successDiv.remove();
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