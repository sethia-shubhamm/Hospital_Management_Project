document.addEventListener('DOMContentLoaded', function() {
    
    let currentUser = null;
    const API_URL = '/api';
    
    
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const sidebarToggle = document.getElementById('sidebarCollapse');
    const profileUpload = document.getElementById('profile-upload');
    const profileImage = document.querySelector('.profile-image');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const errorAlert = document.getElementById('errorAlert');
    const doctorNameElements = document.querySelectorAll('.doctor-name');
    const doctorSpecialtyElements = document.querySelectorAll('.doctor-specialty');
    
    
    const personalInfoForm = document.getElementById('personalInfoForm');
    const contactInfoForm = document.getElementById('contactInfoForm');
    const medicalInfoForm = document.getElementById('medicalInfoForm');
    const educationForm = document.getElementById('educationForm');
    const passwordForm = document.getElementById('passwordForm');
    
    
    initializePage();
    
    
    async function initializePage() {
        setupEventListeners();
        
        
        if (!checkAuthentication()) {
            window.location.href = '/login.html';
            return;
        }
        
        try {
            showLoading(true);
            await loadDoctorData();
            populateProfileData();
            showLoading(false);
        } catch (error) {
            console.error('Error initializing page:', error);
            showError('Failed to load doctor profile data. Please try again later.');
            showLoading(false);
        }
    }
    
    
    function checkAuthentication() {
        const token = localStorage.getItem('token');
        const user = localStorage.getItem('user');
        
        if (!token || !user) {
            return false;
        }
        
        try {
            currentUser = JSON.parse(user);
            return true;
        } catch (e) {
            console.error('Error parsing user data:', e);
            return false;
        }
    }
    
    
    function setupEventListeners() {
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                content.classList.toggle('active');
            });
        }
        
        
        checkWidth();
        window.addEventListener('resize', checkWidth);
        
        
        if (profileUpload && profileImage) {
            profileUpload.addEventListener('change', handleProfileImageUpload);
        }
        
        
        if (personalInfoForm) {
            personalInfoForm.addEventListener('submit', handlePersonalInfoSubmit);
        }
        
        if (contactInfoForm) {
            contactInfoForm.addEventListener('submit', handleContactInfoSubmit);
        }
        
        if (medicalInfoForm) {
            medicalInfoForm.addEventListener('submit', handleMedicalInfoSubmit);
        }
        
        if (educationForm) {
            educationForm.addEventListener('submit', handleEducationSubmit);
        }
        
        if (passwordForm) {
            passwordForm.addEventListener('submit', handlePasswordChange);
        }
    }
    
    
    function handleProfileImageUpload(event) {
        const file = event.target.files[0];
        if (file && file.type.match('image.*')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result;
                
                
                uploadProfileImage(file);
            };
            reader.readAsDataURL(file);
        }
    }
    
    
    async function uploadProfileImage(file) {
        try {
            const formData = new FormData();
            formData.append('profileImage', file);
            
            const response = await fetch(`${API_URL}/doctors/profile-image/${currentUser.id}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Failed to upload profile image');
            }
            
            showFormSuccess('Profile image updated successfully!');
        } catch (error) {
            console.error('Error uploading profile image:', error);
            showError('Failed to upload profile image. Please try again.');
        }
    }
    
    
    async function loadDoctorData() {
        try {
            const response = await fetch(`${API_URL}/doctors/${currentUser.id}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch doctor data');
            }
            
            const data = await response.json();
            currentUser = { ...currentUser, ...data };
            
            
            localStorage.setItem('user', JSON.stringify(currentUser));
            
            return data;
        } catch (error) {
            console.error('Error loading doctor data:', error);
            throw error;
        }
    }
    
    
    function populateProfileData() {
        if (!currentUser) return;
        
        
        doctorNameElements.forEach(element => {
            element.textContent = `${currentUser.firstName} ${currentUser.lastName}`;
        });
        
        doctorSpecialtyElements.forEach(element => {
            element.textContent = currentUser.specialty || '';
        });
        
        
        if (currentUser.profileImage && profileImage) {
            profileImage.src = currentUser.profileImage;
        }
        
        
        if (personalInfoForm) {
            const firstNameInput = personalInfoForm.querySelector('#firstName');
            const lastNameInput = personalInfoForm.querySelector('#lastName');
            const dobInput = personalInfoForm.querySelector('#dateOfBirth');
            const genderInput = personalInfoForm.querySelector('#gender');
            
            if (firstNameInput) firstNameInput.value = currentUser.firstName || '';
            if (lastNameInput) lastNameInput.value = currentUser.lastName || '';
            if (dobInput) dobInput.value = currentUser.dateOfBirth || '';
            if (genderInput) genderInput.value = currentUser.gender || '';
        }
        
        
        if (contactInfoForm) {
            const emailInput = contactInfoForm.querySelector('#email');
            const phoneInput = contactInfoForm.querySelector('#phone');
            const addressInput = contactInfoForm.querySelector('#address');
            const cityInput = contactInfoForm.querySelector('#city');
            const stateInput = contactInfoForm.querySelector('#state');
            const zipInput = contactInfoForm.querySelector('#zip');
            
            if (emailInput) emailInput.value = currentUser.email || '';
            if (phoneInput) phoneInput.value = currentUser.phone || '';
            if (addressInput) addressInput.value = currentUser.address || '';
            if (cityInput) cityInput.value = currentUser.city || '';
            if (stateInput) stateInput.value = currentUser.state || '';
            if (zipInput) zipInput.value = currentUser.zip || '';
        }
        
        
        if (medicalInfoForm) {
            const specialtyInput = medicalInfoForm.querySelector('#specialty');
            const licenseInput = medicalInfoForm.querySelector('#license');
            const experienceInput = medicalInfoForm.querySelector('#experience');
            const hospitalInput = medicalInfoForm.querySelector('#hospital');
            
            if (specialtyInput) specialtyInput.value = currentUser.specialty || '';
            if (licenseInput) licenseInput.value = currentUser.licenseNumber || '';
            if (experienceInput) experienceInput.value = currentUser.yearsOfExperience || '';
            if (hospitalInput) hospitalInput.value = currentUser.hospital || '';
        }
        
        
        populateEducation();
        populateCertifications();
    }
    
    
    function populateEducation() {
        const educationContainer = document.querySelector('.education-list');
        if (!educationContainer || !currentUser.education) return;
        
        
        educationContainer.innerHTML = '';
        
        
        if (Array.isArray(currentUser.education)) {
            currentUser.education.forEach(edu => {
                const educationItem = document.createElement('div');
                educationItem.className = 'mb-3 p-3 border rounded';
                educationItem.innerHTML = `
                    <h5>${edu.degree}</h5>
                    <p>${edu.institution}, ${edu.year}</p>
                    ${edu.description ? `<p class="text-muted">${edu.description}</p>` : ''}
                `;
                educationContainer.appendChild(educationItem);
            });
        }
    }
    
    
    function populateCertifications() {
        const certContainer = document.querySelector('.certification-list');
        if (!certContainer || !currentUser.certifications) return;
        
        
        certContainer.innerHTML = '';
        
        
        if (Array.isArray(currentUser.certifications)) {
            currentUser.certifications.forEach(cert => {
                const certItem = document.createElement('div');
                certItem.className = 'mb-3 p-3 border rounded';
                certItem.innerHTML = `
                    <h5>${cert.name}</h5>
                    <p>Issued by: ${cert.issuedBy}</p>
                    <p>Year: ${cert.year}</p>
                    ${cert.expiryDate ? `<p>Expires: ${cert.expiryDate}</p>` : ''}
                `;
                certContainer.appendChild(certItem);
            });
        }
    }
    
    
    async function handlePersonalInfoSubmit(e) {
        e.preventDefault();
        await updateDoctorProfile(new FormData(personalInfoForm), 'Personal information updated successfully!');
    }
    
    async function handleContactInfoSubmit(e) {
        e.preventDefault();
        await updateDoctorProfile(new FormData(contactInfoForm), 'Contact information updated successfully!');
    }
    
    async function handleMedicalInfoSubmit(e) {
        e.preventDefault();
        await updateDoctorProfile(new FormData(medicalInfoForm), 'Medical information updated successfully!');
    }
    
    async function handleEducationSubmit(e) {
        e.preventDefault();
        await updateDoctorProfile(new FormData(educationForm), 'Education information updated successfully!');
    }
    
    async function handlePasswordChange(e) {
        e.preventDefault();
        
        const currentPassword = passwordForm.querySelector('#currentPassword').value;
        const newPassword = passwordForm.querySelector('#newPassword').value;
        const confirmPassword = passwordForm.querySelector('#confirmPassword').value;
        
        if (newPassword !== confirmPassword) {
            showError('New passwords do not match.');
            return;
        }
        
        try {
            const response = await fetch(`${API_URL}/doctors/change-password/${currentUser.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({
                    currentPassword,
                    newPassword
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to update password');
            }
            
            showFormSuccess('Password updated successfully!');
            passwordForm.reset();
        } catch (error) {
            console.error('Error updating password:', error);
            showError(error.message || 'Failed to update password. Please try again.');
        }
    }
    
    
    async function updateDoctorProfile(formData, successMessage) {
        try {
            
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            const response = await fetch(`${API_URL}/doctors/${currentUser.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to update profile');
            }
            
            
            await loadDoctorData();
            populateProfileData();
            
            showFormSuccess(successMessage);
        } catch (error) {
            console.error('Error updating profile:', error);
            showError(error.message || 'Failed to update profile. Please try again.');
        }
    }
    
    
    function checkWidth() {
        if (window.innerWidth < 992) {
            sidebar.classList.add('active');
            content.classList.remove('active');
        } else {
            sidebar.classList.remove('active');
            content.classList.add('active');
        }
    }
    
    function showLoading(isLoading) {
        if (loadingSpinner) {
            loadingSpinner.style.display = isLoading ? 'flex' : 'none';
        }
    }
    
    function showError(message) {
        if (errorAlert) {
            errorAlert.textContent = message;
            errorAlert.style.display = 'block';
            
            
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, 5000);
        }
    }
    
    function showFormSuccess(message) {
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
            
            
            setTimeout(() => {
                const dismissButton = alertDiv.querySelector('.btn-close');
                if (dismissButton) {
                    dismissButton.click();
                }
            }, 5000);
        }
    }
}); 