
let currentDoctor = null;
let patientsList = [];
let filteredPatients = [];
let currentPatientId = null;


const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');
const sidebarToggle = document.getElementById('sidebarToggle');
const searchInput = document.getElementById('searchPatients');
const patientTable = document.getElementById('patientTable');
const patientTableBody = document.getElementById('patientTableBody');
const patientCount = document.getElementById('patientCount');
const loadingSpinner = document.getElementById('loadingSpinner');
const errorAlert = document.getElementById('errorAlert');
const toastContainer = document.getElementById('toastContainer');
const logoutButtons = document.querySelectorAll('.logout');


document.addEventListener('DOMContentLoaded', function() {
    initializePage();
});


async function initializePage() {
    
    setupEventListeners();
    
    
    if (!checkAuthentication()) {
        return;
    }
    
    try {
        
        await loadDoctorData();
        
        
        await loadPatients();
        
        
        renderPatientTable(patientsList);
    } catch (error) {
        showError(error.message || 'An error occurred while initializing the page.');
    }
}


function setupEventListeners() {
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
    });
    
    
    searchInput.addEventListener('input', function() {
        searchPatients(this.value);
    });
    
    
    logoutButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    });
    
    
    document.getElementById('addPatientForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addNewPatient();
    });
    
    
    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        scheduleAppointment();
    });
    
    
    document.getElementById('editMedicalConditionBtn').addEventListener('click', function() {
        toggleMedicalConditionEditMode(true);
    });
    
    document.getElementById('cancelMedicalConditionBtn').addEventListener('click', function() {
        toggleMedicalConditionEditMode(false);
    });
    
    document.getElementById('saveMedicalConditionBtn').addEventListener('click', function() {
        
        const patientId = currentPatientId;
        saveMedicalCondition(patientId);
    });
    
    
    document.getElementById('editTreatmentBtn').addEventListener('click', function() {
        toggleTreatmentEditMode(true);
    });
    
    document.getElementById('cancelTreatmentBtn').addEventListener('click', function() {
        toggleTreatmentEditMode(false);
    });
    
    document.getElementById('saveTreatmentBtn').addEventListener('click', function() {
        
        const patientId = currentPatientId;
        saveTreatmentPlan(patientId);
    });
}


function checkAuthentication() {
    const token = localStorage.getItem('authToken');
    const userType = localStorage.getItem('userType');
    
    if (!token || userType !== 'doctor') {
        window.location.href = '../../login/index.html';
        return false;
    }
    
    return true;
}


async function loadDoctorData() {
    try {
        
        const doctorName = document.querySelectorAll('.doctor-name');
        doctorName.forEach(el => el.textContent = 'Loading...');
        
        
        const doctorId = localStorage.getItem('userId');
        
        
        const response = await fetch(`/api/doctors/${doctorId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to load doctor data');
        }
        
        const doctorData = await response.json();
        currentDoctor = doctorData;
        
        
        doctorName.forEach(el => el.textContent = `Dr. ${doctorData.firstName} ${doctorData.lastName}`);
        
        return doctorData;
    } catch (error) {
        console.error('Error loading doctor data:', error);
        showError('Failed to load doctor information. Please refresh the page.');
        return null;
    }
}


async function loadPatients() {
    try {
        
        loadingSpinner.style.display = 'block';
        patientTableBody.innerHTML = '';
        
        
        const doctorId = localStorage.getItem('userId');
        const response = await fetch(`/api/doctors/${doctorId}/patients`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to load patients');
        }
        
        
        const data = await response.json();
        patientsList = data;
        filteredPatients = [...patientsList];
        
        
        patientCount.textContent = patientsList.length;
        
        return data;
    } catch (error) {
        console.error('Error loading patients:', error);
        showError('Failed to load patients. Please try again later.');
        return [];
    } finally {
        
        loadingSpinner.style.display = 'none';
    }
}


function renderPatientTable(patients) {
    
    patientTableBody.innerHTML = '';
    
    
    patientCount.textContent = patients.length;
    
    
    if (patients.length === 0) {
        patientTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <p>No patients found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    
    patients.forEach(patient => {
        const row = document.createElement('tr');
        
        
        const dob = new Date(patient.dateOfBirth);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        
        
        let lastVisitDate = 'Never';
        if (patient.lastVisit) {
            const date = new Date(patient.lastVisit);
            lastVisitDate = date.toLocaleDateString();
        }
        
        
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <img src="${patient.image || '/images/default-avatar.png'}" alt="${patient.firstName}" class="rounded-circle" width="45" height="45">
                    </div>
                    <div>
                        <h6 class="mb-0">${patient.firstName} ${patient.lastName}</h6>
                        <small class="text-muted">#${patient.id}</small>
                    </div>
                </div>
            </td>
            <td>${age}</td>
            <td>${patient.gender}</td>
            <td>${patient.phone}</td>
            <td>${lastVisitDate}</td>
            <td><span class="badge rounded-pill ${getStatusClass(patient.status)}">${patient.status || 'Active'}</span></td>
            <td>
                <div class="action-buttons">
                    <button type="button" class="btn btn-outline-primary btn-sm view-patient" data-id="${patient.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm schedule-appointment" data-id="${patient.id}" data-name="${patient.firstName} ${patient.lastName}">
                        <i class="fas fa-calendar-plus"></i>
                    </button>
                </div>
            </td>
        `;
        
        patientTableBody.appendChild(row);
    });
    
    
    addTableActionListeners();
}


function addTableActionListeners() {
    
    document.querySelectorAll('.view-patient').forEach(button => {
        button.addEventListener('click', function() {
            const patientId = this.getAttribute('data-id');
            viewPatientDetails(patientId);
        });
    });
    
    
    document.querySelectorAll('.schedule-appointment').forEach(button => {
        button.addEventListener('click', function() {
            const patientId = this.getAttribute('data-id');
            const patientName = this.getAttribute('data-name');
            openScheduleAppointmentModal(patientId, patientName);
        });
    });
}


function searchPatients(query) {
    query = query.toLowerCase().trim();
    
    if (query === '') {
        filteredPatients = [...patientsList];
    } else {
        filteredPatients = patientsList.filter(patient => {
            return (
                `${patient.firstName} ${patient.lastName}`.toLowerCase().includes(query) ||
                patient.id.toString().includes(query) ||
                patient.phone.toLowerCase().includes(query) ||
                patient.email.toLowerCase().includes(query) ||
                (patient.status && patient.status.toLowerCase().includes(query))
            );
        });
    }
    
    renderPatientTable(filteredPatients);
}


async function viewPatientDetails(patientId) {
    try {
        
        currentPatientId = patientId;
        
        
        const patient = patientsList.find(p => p.id.toString() === patientId.toString());
        
        if (!patient) {
            throw new Error('Patient not found');
        }
        
        
        document.getElementById('patientName').textContent = `${patient.firstName} ${patient.lastName}`;
        document.getElementById('patientDetailsId').textContent = `#${patient.id}`;
        document.getElementById('patientDetailsAge').textContent = calculateAge(patient.dateOfBirth);
        document.getElementById('patientDetailsGender').textContent = patient.gender;
        document.getElementById('patientDetailsPhone').textContent = patient.phone;
        document.getElementById('patientDetailsEmail').textContent = patient.email;
        document.getElementById('patientDetailsAddress').textContent = patient.address || 'Not provided';
        document.getElementById('patientDetailsBloodGroup').textContent = patient.bloodGroup || 'Not provided';
        document.getElementById('patientDetailsAllergies').textContent = patient.allergies || 'None reported';
        
        
        document.getElementById('patientDetailsImage').src = patient.image || '/images/default-avatar.png';
        
        
        await loadPatientMedicalInfo(patientId);
        
        
        await loadPatientAppointments(patientId);
        
        
        const patientDetailsModal = new bootstrap.Modal(document.getElementById('patientDetailsModal'));
        patientDetailsModal.show();
    } catch (error) {
        console.error('Error viewing patient details:', error);
        showToast('error', 'Failed to load patient details');
    }
}


function openScheduleAppointmentModal(patientId, patientName) {
    document.getElementById('appointmentPatientName').textContent = patientName;
    document.getElementById('appointmentForm').setAttribute('data-patient-id', patientId);
    
    
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('appointmentDate').setAttribute('min', today);
    
    
    document.getElementById('appointmentForm').reset();
    
    
    const scheduleAppointmentModal = new bootstrap.Modal(document.getElementById('scheduleAppointmentModal'));
    scheduleAppointmentModal.show();
}


async function loadPatientAppointments(patientId) {
    try {
        const container = document.getElementById('appointmentsContainer');
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div><p class="mt-2">Loading appointments...</p></div>';
        
        
        const response = await fetch(`/api/patients/${patientId}/appointments`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to load appointments');
        }
        
        const data = await response.json();
        
        
        if (data.length === 0) {
            container.innerHTML = `
                <div class="text-center py-3">
                    <p>No appointments found</p>
                    <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#scheduleAppointmentModal" onclick="document.getElementById('patientDetailsModal').classList.remove('show')">
                        <i class="fas fa-calendar-plus me-2"></i>Schedule Now
                    </button>
                </div>
            `;
            return;
        }
        
        
        const today = new Date();
        const upcoming = data.filter(apt => new Date(apt.date) >= today);
        const past = data.filter(apt => new Date(apt.date) < today);
        
        
        let html = '<div class="appointment-list">';
        
        if (upcoming.length > 0) {
            html += '<h6 class="mb-3">Upcoming Appointments</h6>';
            
            upcoming.forEach(apt => {
                const date = new Date(apt.date).toLocaleDateString();
                const time = apt.time;
                
                html += `
                    <div class="appointment-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${apt.reason}</h6>
                                <div>${date} at ${time}</div>
                            </div>
                            <span class="badge ${getAppointmentStatusClass(apt.status)}">${apt.status}</span>
                        </div>
                    </div>
                `;
            });
        }
        
        if (past.length > 0) {
            html += '<h6 class="mb-3 mt-4">Past Appointments</h6>';
            
            past.slice(0, 5).forEach(apt => {
                const date = new Date(apt.date).toLocaleDateString();
                const time = apt.time;
                
                html += `
                    <div class="appointment-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${apt.reason}</h6>
                                <div>${date} at ${time}</div>
                            </div>
                            <span class="badge ${getAppointmentStatusClass(apt.status)}">${apt.status}</span>
                        </div>
                    </div>
                `;
            });
            
            if (past.length > 5) {
                html += `<div class="text-center mt-3"><a href="../appointments/" class="btn btn-sm btn-outline-primary">View All Appointments</a></div>`;
            }
        }
        
        html += '</div>';
        container.innerHTML = html;
    } catch (error) {
        console.error('Error loading appointments:', error);
        document.getElementById('appointmentsContainer').innerHTML = '<div class="alert alert-danger">Failed to load appointments</div>';
    }
}


async function scheduleAppointment() {
    try {
        
        const form = document.getElementById('appointmentForm');
        const patientId = form.getAttribute('data-patient-id');
        const date = document.getElementById('appointmentDate').value;
        const time = document.getElementById('appointmentTime').value;
        const reason = document.getElementById('appointmentReason').value;
        
        
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        
        
        const appointmentData = {
            patientId: patientId,
            doctorId: localStorage.getItem('userId'),
            date: date,
            time: time,
            reason: reason,
            status: 'Scheduled'
        };
        
        
        const response = await fetch('/api/appointments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify(appointmentData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to schedule appointment');
        }
        
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('scheduleAppointmentModal'));
        modal.hide();
        
        
        showToast('success', 'Appointment scheduled successfully');
        
        
        await loadPatients();
        renderPatientTable(patientsList);
    } catch (error) {
        console.error('Error scheduling appointment:', error);
        showToast('error', 'Failed to schedule appointment');
    }
}


async function addNewPatient() {
    try {
        
        const form = document.getElementById('addPatientForm');
        
        
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        
        
        const firstName = document.getElementById('firstName').value;
        const lastName = document.getElementById('lastName').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const gender = document.getElementById('gender').value;
        const dateOfBirth = document.getElementById('dateOfBirth').value;
        const bloodGroup = document.getElementById('bloodGroup').value || null;
        const allergies = document.getElementById('allergies').value || null;
        const address = document.getElementById('address').value || null;
        
        const patientData = {
            firstName,
            lastName,
            email,
            phone,
            gender,
            dateOfBirth,
            bloodGroup,
            allergies,
            address,
            doctorId: localStorage.getItem('userId'),
            status: 'Active'
        };
        
        
        const response = await fetch('/api/patients', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify(patientData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to add patient');
        }
        
        
        const newPatient = await response.json();
        patientsList.push(newPatient);
        
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('addPatientModal'));
        modal.hide();
        
        
        form.reset();
        form.classList.remove('was-validated');
        
        
        showToast('success', 'Patient added successfully');
        
        
        renderPatientTable(patientsList);
    } catch (error) {
        console.error('Error adding patient:', error);
        showToast('error', 'Failed to add patient');
    }
}


function logout() {
    
    localStorage.removeItem('authToken');
    localStorage.removeItem('userId');
    localStorage.removeItem('userType');
    
    
    window.location.href = '../../login/index.html';
}


function getStatusClass(status) {
    switch (status) {
        case 'Active':
            return 'status-active';
        case 'Pending':
            return 'status-pending';
        case 'Inactive':
            return 'status-inactive';
        default:
            return 'status-active';
    }
}


function getAppointmentStatusClass(status) {
    switch (status) {
        case 'Scheduled':
            return 'bg-primary';
        case 'Completed':
            return 'bg-success';
        case 'Cancelled':
            return 'bg-danger';
        case 'No-show':
            return 'bg-warning';
        default:
            return 'bg-secondary';
    }
}


function calculateAge(dateOfBirth) {
    const dob = new Date(dateOfBirth);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    return age;
}


function showError(message) {
    errorAlert.textContent = message;
    errorAlert.style.display = 'block';
    
    
    loadingSpinner.style.display = 'none';
}


function showToast(type, message) {
    const toastId = 'toast-' + Date.now();
    const toastEl = document.createElement('div');
    toastEl.className = 'toast';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.id = toastId;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    const colorClass = type === 'success' ? 'text-success' : 'text-danger';
    
    toastEl.innerHTML = `
        <div class="toast-header">
            <i class="fas ${icon} ${colorClass} me-2"></i>
            <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
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
    
    
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}


async function loadPatientMedicalInfo(patientId) {
    try {
        const response = await fetch(`save_medical_info.php?patient_id=${patientId}`);
        const data = await response.json();
        
        if (data.success) {
            
            const medicalConditionText = document.getElementById('medicalConditionText');
            const medicalConditionInput = document.getElementById('medicalConditionInput');
            
            if (data.data.medical_condition) {
                medicalConditionText.textContent = data.data.medical_condition;
            } else {
                medicalConditionText.textContent = 'No medical condition recorded.';
            }
            
            
            const treatmentText = document.getElementById('treatmentText');
            const treatmentInput = document.getElementById('treatmentInput');
            const medicationInput = document.getElementById('medicationInput');
            
            if (data.data.treatment_plan) {
                treatmentText.textContent = data.data.treatment_plan;
            } else {
                treatmentText.textContent = 'No treatment plan recorded.';
            }
            
            
            medicalConditionInput.value = data.data.medical_condition || '';
            treatmentInput.value = data.data.treatment_plan || '';
            medicationInput.value = data.data.medications || '';
            
            
            updateMedicalHistoryList(data.data.history || []);
        } else {
            showToast('error', data.message || 'Failed to load medical information');
        }
    } catch (error) {
        console.error('Error loading medical information:', error);
        showToast('error', 'An error occurred while loading medical information');
    }
}


function updateMedicalHistoryList(history) {
    const medicalRecordsList = document.getElementById('medicalRecordsList');
    medicalRecordsList.innerHTML = '';
    
    if (history.length === 0) {
        medicalRecordsList.innerHTML = '<li class="list-group-item text-muted">No past medical records found.</li>';
        return;
    }
    
    history.forEach(record => {
        const recordDate = new Date(record.record_date);
        const formattedDate = recordDate.toLocaleDateString() + ' ' + recordDate.toLocaleTimeString();
        
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <strong>${record.record_type}</strong>
                <small class="text-muted">${formattedDate}</small>
            </div>
            <p class="mb-1">${record.details.replace(/\n/g, '<br>')}</p>
            <small class="text-muted">Updated by ${record.doctor_name || 'Doctor'}</small>
        `;
        
        medicalRecordsList.appendChild(li);
    });
}


async function saveMedicalCondition(patientId) {
    try {
        const medicalConditionInput = document.getElementById('medicalConditionInput');
        const medicalConditionValue = medicalConditionInput.value.trim();
        
        const formData = new FormData();
        formData.append('patient_id', patientId);
        formData.append('medical_condition', medicalConditionValue);
        formData.append('type', 'medical_condition');
        
        const response = await fetch('save_medical_info.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            
            const medicalConditionText = document.getElementById('medicalConditionText');
            medicalConditionText.textContent = medicalConditionValue || 'No medical condition recorded.';
            
            
            toggleMedicalConditionEditMode(false);
            
            
            showToast('success', 'Medical condition updated successfully');
            
            
            loadPatientMedicalInfo(patientId);
        } else {
            showToast('error', data.message || 'Failed to save medical condition');
        }
    } catch (error) {
        console.error('Error saving medical condition:', error);
        showToast('error', 'An error occurred while saving medical condition');
    }
}


async function saveTreatmentPlan(patientId) {
    try {
        const treatmentInput = document.getElementById('treatmentInput');
        const medicationInput = document.getElementById('medicationInput');
        
        const treatmentValue = treatmentInput.value.trim();
        const medicationValue = medicationInput.value.trim();
        
        const formData = new FormData();
        formData.append('patient_id', patientId);
        formData.append('treatment_plan', treatmentValue);
        formData.append('medications', medicationValue);
        formData.append('type', 'treatment_plan');
        
        const response = await fetch('save_medical_info.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            
            const treatmentText = document.getElementById('treatmentText');
            treatmentText.textContent = treatmentValue || 'No treatment plan recorded.';
            
            
            toggleTreatmentEditMode(false);
            
            
            showToast('success', 'Treatment plan updated successfully');
            
            
            loadPatientMedicalInfo(patientId);
        } else {
            showToast('error', data.message || 'Failed to save treatment plan');
        }
    } catch (error) {
        console.error('Error saving treatment plan:', error);
        showToast('error', 'An error occurred while saving treatment plan');
    }
}


function toggleMedicalConditionEditMode(isEdit) {
    const medicalConditionView = document.getElementById('medicalConditionView');
    const medicalConditionEdit = document.getElementById('medicalConditionEdit');
    
    if (isEdit) {
        medicalConditionView.style.display = 'none';
        medicalConditionEdit.style.display = 'block';
    } else {
        medicalConditionView.style.display = 'block';
        medicalConditionEdit.style.display = 'none';
    }
}


function toggleTreatmentEditMode(isEdit) {
    const treatmentView = document.getElementById('treatmentView');
    const treatmentEdit = document.getElementById('treatmentEdit');
    
    if (isEdit) {
        treatmentView.style.display = 'none';
        treatmentEdit.style.display = 'block';
    } else {
        treatmentView.style.display = 'block';
        treatmentEdit.style.display = 'none';
    }
} 