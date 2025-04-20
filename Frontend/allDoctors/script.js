
let currentDoctorId = null;
const appointmentModal = document.getElementById('appointmentModal') ? 
    new bootstrap.Modal(document.getElementById('appointmentModal')) : null;
const appointmentForm = document.getElementById('appointmentForm');
const patientIdSelect = document.getElementById('patientId');
const appointmentDate = document.getElementById('appointmentDate');
const appointmentTime = document.getElementById('appointmentTime');
const appointmentReason = document.getElementById('reason');
const modalDoctorName = document.getElementById('doctorName');
const modalAlert = document.getElementById('modalAlert');
const submitAppointmentBtn = document.getElementById('submitAppointment');


const today = new Date();
const todayFormatted = today.toISOString().split('T')[0];
if (appointmentDate) {
    appointmentDate.min = todayFormatted;
}


document.addEventListener('DOMContentLoaded', function() {
    
    setupEventListeners();
    
    
    if (patientIdSelect) {
        loadPatientIds();
    }
    
    
    setupAppointmentButtons();
});


function setupEventListeners() {
    
    if (submitAppointmentBtn) {
        submitAppointmentBtn.addEventListener('click', submitAppointment);
    }
}


async function loadPatientIds() {
    try {
        const response = await fetch('get_patients.php');
        const data = await response.json();
        
        if (data.success) {
            
            patientIdSelect.innerHTML = '<option value="" selected disabled>Select your Patient ID</option>';
            
            
            data.patients.forEach(patient => {
                const option = document.createElement('option');
                option.value = patient.PatientID;
                option.textContent = `${patient.PatientID} - ${patient.PatientName}`;
                patientIdSelect.appendChild(option);
            });
        } else {
            showAlert('Error loading patient IDs: ' + data.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading patient IDs:', error);
        showAlert('Error loading patient IDs. Please try again later.', 'danger');
    }
}


function setupAppointmentButtons() {
    const bookButtons = document.querySelectorAll('.book-btn');
    
    bookButtons.forEach(button => {
        button.addEventListener('click', function() {
            const doctorCard = this.closest('.doctor-card');
            const doctorInfo = doctorCard.querySelector('.doctor-info');
            const doctorName = doctorInfo.querySelector('h3').textContent;
            
            
            const doctorContainer = doctorCard.closest('[data-specialty]');
            const specialty = doctorContainer.dataset.specialty;
            
            
            
            const specialtyToId = {
                'cardiology': 1,
                'neurology': 2,
                'pediatrics': 3,
                'orthopedics': 4,
                'gynecology': 5,
                'dermatology': 6,
                'ophthalmology': 7,
                'gastroenterology': 8,
                'pulmonology': 9,
                'endocrinology': 10,
                'oncology': 11,
                'nephrology': 12,
                'urology': 13
            };
            
            currentDoctorId = specialtyToId[specialty] || 1;
            
            
            appointmentForm.reset();
            
            
            modalDoctorName.textContent = doctorName;
            
            
            appointmentModal.show();
        });
    });
}


async function submitAppointment() {
    try {
        
        const patientId = patientIdSelect.value;
        const date = appointmentDate.value;
        const time = appointmentTime.value;
        const reason = appointmentReason.value;
        
        
        if (!patientId || !date || !time || !reason) {
            showModalAlert('Please fill in all fields', 'danger');
            return;
        }
        
        
        const appointmentData = {
            patient_id: patientId,
            doctor_id: currentDoctorId,
            appointment_date: date,
            appointment_time: time,
            reason: reason
        };
        
        
        const response = await fetch('book_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(appointmentData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            
            appointmentModal.hide();
            
            
            showAlert('Appointment booked successfully!', 'success');
            
            
            const appointmentSummary = document.getElementById('appointmentSummary');
            if (appointmentSummary) {
                appointmentSummary.innerHTML = `
                    <p><strong>Doctor:</strong> ${data.appointment.doctor}</p>
                    <p><strong>Date:</strong> ${data.appointment.date}</p>
                    <p><strong>Time:</strong> ${data.appointment.time}</p>
                    <p><strong>Reason:</strong> ${data.appointment.reason}</p>
                    <p><strong>Status:</strong> ${data.appointment.status}</p>
                `;
                
                
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            }
        } else {
            showModalAlert(data.message, 'danger');
        }
    } catch (error) {
        console.error('Error submitting appointment:', error);
        showModalAlert('Error booking appointment. Please try again later.', 'danger');
    }
}


function showModalAlert(message, type) {
    modalAlert.textContent = message;
    modalAlert.className = `alert alert-${type}`;
    modalAlert.classList.remove('d-none');
}


function showAlert(message, type) {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
    alertContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    
    const container = document.querySelector('.container');
    container.insertBefore(alertContainer, container.firstChild);
    
    
    setTimeout(() => {
        alertContainer.classList.remove('show');
        setTimeout(() => alertContainer.remove(), 300);
    }, 5000);
}
