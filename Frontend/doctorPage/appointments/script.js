
let currentDoctor = null;
let patientsList = [];
let appointmentsList = [];
let todayAppointments = [];
let upcomingAppointments = [];
let currentSort = { field: 'time', order: 'asc' };


const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');
const sidebarToggle = document.getElementById('sidebarToggle');
const loadingSpinner = document.getElementById('loadingSpinner');
const errorAlert = document.getElementById('errorAlert');
const toastContainer = document.getElementById('toastContainer');
const logoutButtons = document.querySelectorAll('.logout');


const todayAppointmentsSection = document.getElementById('todayAppointmentsSection');
const todayAppointmentsList = document.getElementById('todayAppointmentsList');
const noTodayAppointments = document.getElementById('noTodayAppointments');
const upcomingAppointmentsSection = document.getElementById('upcomingAppointmentsSection');
const upcomingAppointmentsList = document.getElementById('upcomingAppointmentsList');
const upcomingAppointmentsCount = document.getElementById('upcomingAppointmentsCount');
const noUpcomingAppointments = document.getElementById('noUpcomingAppointments');


const scheduleAppointmentModal = document.getElementById('scheduleAppointmentModal');
const patientSelect = document.getElementById('patientSelect');


document.addEventListener('DOMContentLoaded', function() {
    initializePage();
});


async function initializePage() {
    
    setupEventListeners();
    
    
    if (!checkAuthentication()) {
        return;
    }
    
    try {
        
        showLoading(true);
        
        
        await loadDoctorData();
        
        
        await loadPatients();
        
        
        await loadAppointments();
        
        
        processAppointments();
        
        
        renderAppointments();
        
        
        showLoading(false);
    } catch (error) {
        showError(error.message || 'An error occurred while initializing the page.');
        showLoading(false);
    }
}


function setupEventListeners() {
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
    });
    
    
    logoutButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    });
    
    
    document.querySelectorAll('.sort-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const sortField = this.getAttribute('data-sort');
            
            if (currentSort.field === sortField) {
                
                currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
            } else {
                
                currentSort.field = sortField;
                currentSort.order = 'asc';
            }
            
            sortAppointments();
            renderTodayAppointments();
        });
    });
    
    
    document.getElementById('scheduleAppointmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        scheduleAppointment();
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
        throw new Error('Failed to load doctor information');
    }
}


async function loadPatients() {
    try {
        
        patientSelect.innerHTML = '<option value="" selected disabled>Select patient</option>';
        
        
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
        
        
        patientsList.forEach(patient => {
            const option = document.createElement('option');
            option.value = patient.id;
            option.textContent = `${patient.firstName} ${patient.lastName}`;
            patientSelect.appendChild(option);
        });
        
        return data;
    } catch (error) {
        console.error('Error loading patients:', error);
        throw new Error('Failed to load patients list');
    }
}


async function loadAppointments() {
    try {
        
        const doctorId = localStorage.getItem('userId');
        const response = await fetch(`/api/doctors/${doctorId}/appointments`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to load appointments');
        }
        
        
        const data = await response.json();
        appointmentsList = data;
        
        return data;
    } catch (error) {
        console.error('Error loading appointments:', error);
        throw new Error('Failed to load appointments');
    }
}


function processAppointments() {
    const today = new Date();
    today.setHours(0, 0, 0, 0); 
    
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1); 
    
    
    todayAppointments = appointmentsList.filter(appointment => {
        const appointmentDate = new Date(appointment.date);
        appointmentDate.setHours(0, 0, 0, 0);
        return appointmentDate.getTime() === today.getTime();
    });
    
    
    upcomingAppointments = appointmentsList.filter(appointment => {
        const appointmentDate = new Date(appointment.date);
        appointmentDate.setHours(0, 0, 0, 0);
        return appointmentDate.getTime() > today.getTime();
    });
    
    
    sortAppointments();
}


function sortAppointments() {
    const sortFn = (a, b) => {
        let comparison = 0;
        
        if (currentSort.field === 'time') {
            
            const timeA = convertTimeToMinutes(a.time);
            const timeB = convertTimeToMinutes(b.time);
            comparison = timeA - timeB;
        } else if (currentSort.field === 'patient') {
            
            const patientA = getPatientName(a.patientId).toLowerCase();
            const patientB = getPatientName(b.patientId).toLowerCase();
            comparison = patientA.localeCompare(patientB);
        } else if (currentSort.field === 'status') {
            
            comparison = a.status.localeCompare(b.status);
        }
        
        
        return currentSort.order === 'asc' ? comparison : -comparison;
    };
    
    todayAppointments.sort(sortFn);
    upcomingAppointments.sort(sortFn);
}


function convertTimeToMinutes(timeString) {
    const [hours, minutes] = timeString.split(':').map(Number);
    return hours * 60 + minutes;
}


function getPatientName(patientId) {
    const patient = patientsList.find(p => p.id.toString() === patientId.toString());
    return patient ? `${patient.firstName} ${patient.lastName}` : 'Unknown Patient';
}


function renderAppointments() {
    renderTodayAppointments();
    renderUpcomingAppointments();
}


function renderTodayAppointments() {
    
    todayAppointmentsList.innerHTML = '';
    
    
    if (todayAppointments.length === 0) {
        todayAppointmentsList.style.display = 'none';
        noTodayAppointments.style.display = 'block';
    } else {
        todayAppointmentsList.style.display = 'block';
        noTodayAppointments.style.display = 'none';
        
        
        todayAppointments.forEach(appointment => {
            const appointmentCard = createAppointmentCard(appointment);
            todayAppointmentsList.appendChild(appointmentCard);
        });
    }
    
    
    todayAppointmentsSection.style.display = 'block';
}


function renderUpcomingAppointments() {
    
    upcomingAppointmentsList.innerHTML = '';
    
    
    upcomingAppointmentsCount.textContent = upcomingAppointments.length;
    
    
    if (upcomingAppointments.length === 0) {
        upcomingAppointmentsList.style.display = 'none';
        noUpcomingAppointments.style.display = 'block';
    } else {
        upcomingAppointmentsList.style.display = 'block';
        noUpcomingAppointments.style.display = 'none';
        
        
        upcomingAppointments.forEach(appointment => {
            const appointmentCard = createAppointmentCard(appointment);
            upcomingAppointmentsList.appendChild(appointmentCard);
        });
    }
    
    
    upcomingAppointmentsSection.style.display = 'block';
}


function createAppointmentCard(appointment) {
    
    const patient = patientsList.find(p => p.id.toString() === appointment.patientId.toString());
    
    
    const col = document.createElement('div');
    col.className = 'col-lg-6 col-xl-4 mb-4';
    
    
    const appointmentDate = new Date(appointment.date);
    const formattedDate = appointmentDate.toLocaleDateString();
    
    
    const duration = appointment.duration ? `${appointment.duration} min` : '30 min';
    
    
    col.innerHTML = `
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">${appointment.time}</h5>
                        <small class="text-muted">${duration}</small>
                    </div>
                    <span class="badge ${getAppointmentStatusClass(appointment.status)}">${appointment.status}</span>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <img src="${patient?.image || '/images/default-avatar.png'}" alt="${patient?.firstName || 'Patient'}" class="rounded-circle" width="50" height="50">
                    </div>
                    <div>
                        <h6 class="mb-0">${patient ? `${patient.firstName} ${patient.lastName}` : 'Unknown Patient'}</h6>
                        <small class="text-muted">${appointment.type || 'Consultation'}</small>
                        <div class="mt-1"><small class="text-muted">${appointment.id}</small></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <button class="btn btn-sm btn-primary view-appointment" data-id="${appointment.id}">
                        <i class="fas fa-eye me-1"></i> View
                    </button>
                    ${getActionButtonsHtml(appointment)}
                </div>
            </div>
        </div>
    `;
    
    
    setTimeout(() => {
        const viewBtn = col.querySelector('.view-appointment');
        viewBtn.addEventListener('click', () => viewAppointmentDetails(appointment.id));
        
        const actionBtns = col.querySelectorAll('.action-btn');
        actionBtns.forEach(btn => {
            const action = btn.getAttribute('data-action');
            btn.addEventListener('click', () => handleAppointmentAction(action, appointment.id));
        });
    }, 0);
    
    return col;
}


function getActionButtonsHtml(appointment) {
    const status = appointment.status;
    
    switch (status) {
        case 'Scheduled':
            return `
                <div>
                    <button class="btn btn-sm btn-outline-success action-btn" data-action="start" data-id="${appointment.id}">
                        <i class="fas fa-play me-1"></i> Start
                    </button>
                    <button class="btn btn-sm btn-outline-danger action-btn" data-action="cancel" data-id="${appointment.id}">
                        <i class="fas fa-times me-1"></i>
                    </button>
                </div>
            `;
        case 'In Progress':
            return `
                <div>
                    <button class="btn btn-sm btn-outline-success action-btn" data-action="complete" data-id="${appointment.id}">
                        <i class="fas fa-check me-1"></i> Complete
                    </button>
                </div>
            `;
        case 'Completed':
            return `
                <div>
                    <button class="btn btn-sm btn-outline-primary action-btn" data-action="addNote" data-id="${appointment.id}">
                        <i class="fas fa-sticky-note me-1"></i>
                    </button>
                </div>
            `;
        case 'Cancelled':
            return `
                <div>
                    <button class="btn btn-sm btn-outline-secondary action-btn" data-action="reschedule" data-id="${appointment.id}">
                        <i class="fas fa-redo me-1"></i>
                    </button>
                </div>
            `;
        default:
            return '';
    }
}


async function viewAppointmentDetails(appointmentId) {
    try {
        
        const appointment = [...todayAppointments, ...upcomingAppointments]
            .find(a => a.id.toString() === appointmentId.toString());
        
        if (!appointment) {
            throw new Error('Appointment not found');
        }
        
        
        const patient = patientsList.find(p => p.id.toString() === appointment.patientId.toString());
        
        
        const appointmentDate = new Date(appointment.date);
        const formattedDate = appointmentDate.toLocaleDateString();
        const formattedDateTime = `${formattedDate} at ${appointment.time}`;
        
        
        document.getElementById('appointmentPatientName').textContent = patient ? `${patient.firstName} ${patient.lastName}` : 'Unknown Patient';
        document.getElementById('appointmentPatientImage').src = patient?.image || '/images/default-avatar.png';
        document.getElementById('appointmentStatus').textContent = appointment.status;
        document.getElementById('appointmentStatus').className = `badge ${getAppointmentStatusClass(appointment.status)}`;
        document.getElementById('appointmentId').textContent = appointment.id;
        document.getElementById('appointmentDateTime').textContent = formattedDateTime;
        document.getElementById('appointmentType').textContent = appointment.type || 'Consultation';
        document.getElementById('appointmentDuration').textContent = appointment.duration ? `${appointment.duration} minutes` : '30 minutes';
        document.getElementById('appointmentReason').textContent = appointment.reason || 'Not specified';
        
        
        const actionsContainer = document.getElementById('appointmentActions');
        actionsContainer.innerHTML = '';
        
        if (appointment.status === 'Scheduled') {
            actionsContainer.innerHTML = `
                <button type="button" class="btn btn-success action-btn" data-action="start" data-id="${appointment.id}">
                    <i class="fas fa-play me-2"></i>Start Appointment
                </button>
                <button type="button" class="btn btn-danger action-btn" data-action="cancel" data-id="${appointment.id}">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            `;
        } else if (appointment.status === 'In Progress') {
            actionsContainer.innerHTML = `
                <button type="button" class="btn btn-success action-btn" data-action="complete" data-id="${appointment.id}">
                    <i class="fas fa-check me-2"></i>Complete Appointment
                </button>
            `;
        } else if (appointment.status === 'Completed') {
            actionsContainer.innerHTML = `
                <button type="button" class="btn btn-primary action-btn" data-action="viewNotes" data-id="${appointment.id}">
                    <i class="fas fa-clipboard me-2"></i>View Notes
                </button>
                <button type="button" class="btn btn-info action-btn" data-action="addNote" data-id="${appointment.id}">
                    <i class="fas fa-plus me-2"></i>Add Note
                </button>
            `;
        } else if (appointment.status === 'Cancelled') {
            actionsContainer.innerHTML = `
                <button type="button" class="btn btn-secondary action-btn" data-action="reschedule" data-id="${appointment.id}">
                    <i class="fas fa-redo me-2"></i>Reschedule
                </button>
            `;
        }
        
        
        setTimeout(() => {
            const actionBtns = actionsContainer.querySelectorAll('.action-btn');
            actionBtns.forEach(btn => {
                const action = btn.getAttribute('data-action');
                btn.addEventListener('click', () => {
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('appointmentDetailsModal'));
                    modal.hide();
                    
                    
                    handleAppointmentAction(action, appointment.id);
                });
            });
        }, 0);
        
        
        const appointmentDetailsModal = new bootstrap.Modal(document.getElementById('appointmentDetailsModal'));
        appointmentDetailsModal.show();
    } catch (error) {
        console.error('Error viewing appointment details:', error);
        showToast('error', 'Failed to load appointment details');
    }
}


async function handleAppointmentAction(action, appointmentId) {
    try {
        
        const appointment = [...todayAppointments, ...upcomingAppointments]
            .find(a => a.id.toString() === appointmentId.toString());
        
        if (!appointment) {
            throw new Error('Appointment not found');
        }
        
        let updatedStatus = appointment.status;
        let message = '';
        
        switch (action) {
            case 'start':
                updatedStatus = 'In Progress';
                message = 'Appointment started successfully';
                break;
            case 'complete':
                updatedStatus = 'Completed';
                message = 'Appointment completed successfully';
                break;
            case 'cancel':
                updatedStatus = 'Cancelled';
                message = 'Appointment cancelled';
                break;
            case 'reschedule':
                
                openRescheduleModal(appointment);
                return;
            case 'addNote':
                
                openAddNoteModal(appointment);
                return;
            case 'viewNotes':
                
                openViewNotesModal(appointment);
                return;
            default:
                throw new Error('Unknown action');
        }
        
        
        const response = await fetch(`/api/appointments/${appointmentId}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify({ status: updatedStatus })
        });
        
        if (!response.ok) {
            throw new Error('Failed to update appointment status');
        }
        
        
        appointment.status = updatedStatus;
        
        
        renderAppointments();
        
        
        showToast('success', message);
    } catch (error) {
        console.error(`Error handling appointment action (${action}):`, error);
        showToast('error', `Failed to ${action} appointment`);
    }
}


async function scheduleAppointment() {
    try {
        const form = document.getElementById('scheduleAppointmentForm');
        
        
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        
        
        const patientId = document.getElementById('patientSelect').value;
        const date = document.getElementById('appointmentDate').value;
        const time = document.getElementById('appointmentTime').value;
        const type = document.getElementById('appointmentType').value;
        const duration = document.getElementById('appointmentDuration').value;
        const reason = document.getElementById('appointmentReason').value;
        
        
        const appointmentData = {
            patientId,
            doctorId: localStorage.getItem('userId'),
            date,
            time,
            type,
            duration,
            reason,
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
        
        
        const newAppointment = await response.json();
        
        
        appointmentsList.push(newAppointment);
        
        
        processAppointments();
        renderAppointments();
        
        
        form.reset();
        form.classList.remove('was-validated');
        const modal = bootstrap.Modal.getInstance(document.getElementById('scheduleAppointmentModal'));
        modal.hide();
        
        
        showToast('success', 'Appointment scheduled successfully');
    } catch (error) {
        console.error('Error scheduling appointment:', error);
        showToast('error', 'Failed to schedule appointment');
    }
}


function openRescheduleModal(appointment) {
    
    
    
    
    document.getElementById('patientSelect').value = appointment.patientId;
    document.getElementById('appointmentType').value = appointment.type || 'Consultation';
    document.getElementById('appointmentReason').value = appointment.reason || '';
    document.getElementById('appointmentDuration').value = appointment.duration || '30';
    
    
    const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleAppointmentModal'));
    scheduleModal.show();
}


function openAddNoteModal(appointment) {
    
    
    showToast('info', 'Add note feature would open here');
}


function openViewNotesModal(appointment) {
    
    
    showToast('info', 'View notes feature would open here');
}


function getAppointmentStatusClass(status) {
    switch (status) {
        case 'Scheduled':
            return 'bg-primary';
        case 'In Progress':
            return 'bg-warning';
        case 'Completed':
            return 'bg-success';
        case 'Cancelled':
            return 'bg-danger';
        case 'No-show':
            return 'bg-secondary';
        default:
            return 'bg-primary';
    }
}


function showLoading(isLoading) {
    loadingSpinner.style.display = isLoading ? 'block' : 'none';
    
    
    if (isLoading) {
        todayAppointmentsSection.style.display = 'none';
        upcomingAppointmentsSection.style.display = 'none';
    }
}


function showError(message) {
    errorAlert.textContent = message;
    errorAlert.style.display = 'block';
}


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
    
    
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}


function logout() {
    
    localStorage.removeItem('authToken');
    localStorage.removeItem('userId');
    localStorage.removeItem('userType');
    
    
    window.location.href = '../../login/index.html';
} 