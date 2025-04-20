<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Patient') {
    $_SESSION['login_error'] = "Please log in as a patient to access this page";
    header("Location: ../../login/index.php");
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = "../error_log.txt";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


$login_id = $_SESSION['user_id'];
$email = $_SESSION['email'];


$patient_query = "SELECT * FROM Patients WHERE PatientID = '$login_id'";
$patient_result = mysqli_query($conn, $patient_query);

if ($patient_result && mysqli_num_rows($patient_result) > 0) {
    $patient = mysqli_fetch_assoc($patient_result);
    $patientName = $patient['PatientName'];
    $patientID = $patient['PatientID'];
} else {
    $patientName = "Patient";
    $patientID = $login_id;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctorID = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $appointmentDate = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointmentTime = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $appointmentReason = isset($_POST['appointment_reason']) ?
        mysqli_real_escape_string($conn, $_POST['appointment_reason']) :
        'Regular Checkup';
    $appointmentDuration = isset($_POST['duration']) ?
        mysqli_real_escape_string($conn, $_POST['duration']) :
        '30';

    
    if (empty($doctorID) || empty($appointmentDate) || empty($appointmentTime)) {
        $error_message = "Please fill in all required fields";
    } else {
        
        $check_status = mysqli_query($conn, "SHOW COLUMNS FROM Appointments LIKE 'Status'");
        $has_status = mysqli_num_rows($check_status) > 0;

        
        $check_doctor_query = "SELECT COUNT(*) as count FROM Appointments 
                              WHERE DoctorID = '$doctorID' 
                              AND AppointmentDate = '$appointmentDate' 
                              AND AppointmentTime = '$appointmentTime'";

        
        if ($has_status) {
            $check_doctor_query .= " AND Status NOT IN ('Cancelled', 'Completed')";
        }

        $doctor_result = mysqli_query($conn, $check_doctor_query);
        $doctor_row = mysqli_fetch_assoc($doctor_result);

        if ($doctor_row['count'] > 0) {
            $error_message = "This doctor is already booked at the selected time. Please choose a different time.";
        }
        
        else {
            $check_patient_query = "SELECT COUNT(*) as count FROM Appointments 
                                   WHERE PatientID = '$patientID' 
                                   AND AppointmentDate = '$appointmentDate' 
                                   AND AppointmentTime = '$appointmentTime'";

            
            if ($has_status) {
                $check_patient_query .= " AND Status NOT IN ('Cancelled', 'Completed')";
            }

            $patient_result = mysqli_query($conn, $check_patient_query);
            $patient_row = mysqli_fetch_assoc($patient_result);

            if ($patient_row['count'] > 0) {
                $error_message = "You already have an appointment at this time. Please choose a different time.";
            } else {
                
                $check_column = mysqli_query($conn, "SHOW COLUMNS FROM Appointments LIKE 'AppointmentPurpose'");
                $has_purpose = mysqli_num_rows($check_column) > 0;

                
                $check_duration = mysqli_query($conn, "SHOW COLUMNS FROM Appointments LIKE 'Duration'");
                $has_duration = mysqli_num_rows($check_duration) > 0;

                
                $status = 'Scheduled';
                $duration = $appointmentDuration;

                
                $insert_query = "INSERT INTO Appointments (PatientID, DoctorID, AppointmentDate, AppointmentTime";

                if ($has_purpose) {
                    $insert_query .= ", AppointmentPurpose";
                }

                if ($has_status) {
                    $insert_query .= ", Status";
                }

                if ($has_duration) {
                    $insert_query .= ", Duration";
                }

                $insert_query .= ") VALUES ('$patientID', '$doctorID', '$appointmentDate', '$appointmentTime'";

                if ($has_purpose) {
                    $insert_query .= ", '$appointmentReason'";
                }

                if ($has_status) {
                    $insert_query .= ", '$status'";
                }

                if ($has_duration) {
                    $insert_query .= ", $duration";
                }

                $insert_query .= ")";

                if (mysqli_query($conn, $insert_query)) {
                    $success_message = "Appointment booked successfully!";
                } else {
                    $error_code = mysqli_errno($conn);
                    $error_text = mysqli_error($conn);

                    
                    if ($error_code == 1062) {
                        $error_message = "This appointment slot is already taken. Please select a different time.";
                    }
                    
                    else if (strpos($error_text, 'already booked') !== false || strpos($error_text, 'already has an appointment') !== false) {
                        $error_message = $error_text;
                    } else {
                        $error_message = "Error booking appointment: " . $error_text;
                        logError($error_message);
                    }
                }
            }
        }
    }
}


$doctors_query = "SELECT * FROM Doctors ORDER BY DoctorName";
$doctors_result = mysqli_query($conn, $doctors_query);


$specialties_query = "SELECT DISTINCT Specialty FROM Doctors WHERE Specialty IS NOT NULL AND Specialty != ''";
$specialties_result = mysqli_query($conn, $specialties_query);
$specialties = [];
if ($specialties_result && mysqli_num_rows($specialties_result) > 0) {
    while ($row = mysqli_fetch_assoc($specialties_result)) {
        $specialties[] = $row['Specialty'];
    }
}


$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$specialty = isset($_GET['specialty']) ? mysqli_real_escape_string($conn, $_GET['specialty']) : '';
$availability = isset($_GET['availability']) ? mysqli_real_escape_string($conn, $_GET['availability']) : '';


$filtered_query = "SELECT * FROM Doctors WHERE 1=1";
if (!empty($search)) {
    $filtered_query .= " AND (DoctorName LIKE '%$search%' OR Specialty LIKE '%$search%')";
}
if (!empty($specialty)) {
    $filtered_query .= " AND Specialty = '$specialty'";
}
$filtered_query .= " ORDER BY DoctorName";
$filtered_result = mysqli_query($conn, $filtered_query);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, width=device-width">

    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
</head>

<body>
    <div class="desktop">
        <div class="navbar">
            <div class="logo">
                <img src="icons/logo.png" alt="Logo">
                <h6>Seattle Grace Hospital</h6>
            </div>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="../../index.php">HOME</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../allDoctors/index.php">ALL DOCTORS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../bloodBank/index.php">BLOOD BANK</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../index.php#contact">CONTACT</a>
                </li>
            </ul>
        </div>

        <div class="mainContainer">
            <div class="choiceSection">
                <div class="menu-items">
                    <div>
                        <img src="../dashboard/icons/dashboard.png" alt="">
                        <a href="../dashboard/index.php" style="text-decoration: none;">
                            <h6>Dashboard</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/profile.png" alt="">
                        <a href="../profile/index.php" style="text-decoration: none;">
                            <h6>Profile</h6>
                        </a>
                    </div>
                    <div class="active">
                        <img src="../dashboard/icons/appointment.png" alt="">
                        <a href="index.php" style="text-decoration: none;">
                            <h6>Appointment</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/records.png" alt="">
                        <a href="../records/index.php" style="text-decoration: none;">
                            <h6>Records</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/invoice.png" alt="">
                        <a href="../payments/index.php" style="text-decoration: none;">
                            <h6>Payments</h6>
                        </a>
                    </div>
                    <div style="height: 52.25px;">
                        <img src="icons/image.png" alt="" style="height: 32.25px;">
                        <a href="../insurance/index.php" style="text-decoration: none;">
                            <h6>Insurance</h6>
                        </a>
                    </div>
                </div>
                <div class="logout">
                    <img src="icons/logout.png" alt="">
                    <a href="../../logout.php" style="text-decoration: none; color: inherit;">
                        <h6>Logout</h6>
                    </a>
                </div>
            </div>

            <div class="content-area">
                <div class="welcome-section">
                    <h1 style="color:white; font-size: larger;">Book an Appointment</h1>
                    <p style="color:white">Select your preferred doctor and time slot</p>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div
                        class="alert <?php echo (strpos($error_message, 'already booked') !== false || strpos($error_message, 'already have an appointment') !== false) ? 'alert-warning booking-conflict' : 'alert-danger'; ?>">
                        <?php if (strpos($error_message, 'already booked') !== false || strpos($error_message, 'already have an appointment') !== false): ?>
                            <strong>⚠️ Booking Conflict: </strong>
                        <?php endif; ?>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="appointment-container">
                    <div class="search-section">
                        <form method="GET" action="index.php" class="filter-form">
                            <div class="search-box">
                                <img src="icons/Search.png" alt="">
                                <input type="text" name="search" placeholder="Search doctors by name or specialty"
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="filter-options">
                                <select name="specialty">
                                    <option value="">All Specialties</option>
                                    <?php foreach ($specialties as $spec): ?>
                                        <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo ($specialty == $spec) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($spec); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="filter-btn">Apply Filters</button>
                                <a href="index.php" class="reset-btn">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="doctors-grid">
                        <?php if ($filtered_result && mysqli_num_rows($filtered_result) > 0): ?>
                            <?php while ($doctor = mysqli_fetch_assoc($filtered_result)): ?>
                                <div class="doctor-card">
                                    <img src="icons/doctor.png" alt="Doctor">
                                    <div class="doctor-info">
                                        <h3><?php echo htmlspecialchars($doctor['DoctorName']); ?></h3>
                                        <p class="specialty">
                                            <?php echo htmlspecialchars($doctor['Specialty'] ?? 'General Physician'); ?>
                                        </p>
                                        <div class="availability">
                                            <span class="available-tag">Available for Booking</span>
                                        </div>
                                        <button class="book-btn"
                                            onclick="openBookingModal(<?php echo $doctor['DoctorID']; ?>, '<?php echo htmlspecialchars($doctor['DoctorName']); ?>')">
                                            Book Appointment
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-results">
                                <p>No doctors found matching your criteria. Please try different filters.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Book Appointment with <span id="doctorName"></span></h2>

            <form method="POST" action="index.php" class="booking-form">
                <input type="hidden" id="doctor_id" name="doctor_id">

                <div class="form-group">
                    <label for="appointment_date">Date:</label>
                    <input type="date" id="appointment_date" name="appointment_date" required
                        min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="appointment_time">Time:</label>
                    <input type="time" id="appointment_time" name="appointment_time" required>
                </div>

                <div class="form-group">
                    <label for="appointment_reason">Reason for Visit:</label>
                    <select id="appointment_reason" name="appointment_reason">
                        <option value="Regular Checkup">Regular Checkup</option>
                        <option value="Follow-up">Follow-up</option>
                        <option value="Consultation">Consultation</option>
                        <option value="Emergency">Emergency</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="appointment_duration">Duration (minutes):</label>
                    <select id="appointment_duration" name="duration">
                        <option value="15">15 minutes</option>
                        <option value="30" selected>30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">60 minutes (1 hour)</option>
                    </select>
                </div>

                <button type="submit" name="book_appointment" class="confirm-btn">Confirm Booking</button>
            </form>
        </div>
    </div>

    <script>
        
        const modal = document.getElementById('bookingModal');
        const doctorNameSpan = document.getElementById('doctorName');
        const doctorIdInput = document.getElementById('doctor_id');
        const closeBtn = document.getElementsByClassName('close-modal')[0];

        function openBookingModal(doctorId, doctorName) {
            doctorNameSpan.textContent = doctorName;
            doctorIdInput.value = doctorId;
            modal.style.display = 'block';

            
            clearAvailabilityWarning();
        }

        closeBtn.onclick = function () {
            modal.style.display = 'none';
        }

        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        
        document.addEventListener('DOMContentLoaded', function () {
            const dateInput = document.getElementById('appointment_date');
            const timeInput = document.getElementById('appointment_time');
            const bookingForm = document.querySelector('.booking-form');

            if (dateInput && timeInput) {
                
                dateInput.addEventListener('change', checkAppointmentAvailability);
                timeInput.addEventListener('change', checkAppointmentAvailability);

                
                if (bookingForm) {
                    bookingForm.addEventListener('submit', function (e) {
                        if (!validateAppointmentForm()) {
                            e.preventDefault();
                        }
                    });
                }
            }
        });

        function validateAppointmentForm() {
            const dateInput = document.getElementById('appointment_date');
            const timeInput = document.getElementById('appointment_time');

            if (!dateInput.value) {
                showAvailabilityWarning('Please select an appointment date');
                return false;
            }

            if (!timeInput.value) {
                showAvailabilityWarning('Please select an appointment time');
                return false;
            }

            return true;
        }

        function checkAppointmentAvailability() {
            const doctorId = document.getElementById('doctor_id').value;
            const appointmentDate = document.getElementById('appointment_date').value;
            const appointmentTime = document.getElementById('appointment_time').value;
            const confirmBtn = document.querySelector('.confirm-btn');

            
            if (!doctorId || !appointmentDate || !appointmentTime) {
                return;
            }

            
            showAvailabilityWarning('Checking availability...', 'checking');

            
            const formData = new FormData();
            formData.append('check_availability', '1');
            formData.append('doctor_id', doctorId);
            formData.append('appointment_date', appointmentDate);
            formData.append('appointment_time', appointmentTime);

            
            fetch('check_availability.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        clearAvailabilityWarning();
                        if (confirmBtn) confirmBtn.removeAttribute('disabled');
                    } else {
                        showAvailabilityWarning(data.message || 'This time slot is not available');
                        if (confirmBtn) confirmBtn.setAttribute('disabled', 'disabled');
                    }
                })
                .catch(error => {
                    console.error('Error checking availability:', error);
                    clearAvailabilityWarning();
                });
        }

        function showAvailabilityWarning(message, type = 'warning') {
            clearAvailabilityWarning();

            const warningDiv = document.createElement('div');
            warningDiv.id = 'availability-warning';
            warningDiv.className = type === 'checking' ? 'availability-checking' : 'availability-warning';
            warningDiv.textContent = message;

            const formGroup = document.getElementById('appointment_time').parentNode;
            formGroup.appendChild(warningDiv);
        }

        function clearAvailabilityWarning() {
            const existingWarning = document.getElementById('availability-warning');
            if (existingWarning) {
                existingWarning.remove();
            }
        }
    </script>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: #555;
        }

        .booking-form {
            margin-top: 20px;
        }

        .booking-form .form-group {
            margin-bottom: 20px;
        }

        .booking-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .booking-form input,
        .booking-form select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .confirm-btn {
            background-color: #7260ff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
        }

        .confirm-btn:hover {
            background-color: #5a48e0;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .filter-btn,
        .reset-btn {
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .filter-btn {
            background-color: #7260ff;
            color: white;
            border: none;
        }

        .reset-btn {
            background-color: #f5f5f5;
            color: #333;
            text-decoration: none;
            border: 1px solid #ddd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 38px;
        }

        .alert {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning.booking-conflict {
            background-color: #fff3cd;
            color: #856404;
            border-left: 5px solid #ffc107;
            animation: pulse 2s infinite;
            font-weight: 500;
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.3);
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }

            70% {
                box-shadow: 0 0 0 15px rgba(255, 193, 7, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
            }
        }

        .no-results {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }

       
        .appointment-container {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin-top: -40px;
            height: auto;
            max-width: 100%;
            overflow: hidden;
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
            max-height: 60vh;
            overflow-y: auto;
            padding: 10px;
            padding-right: 20px;
        }

        .doctor-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            height: auto;
            min-height: 350px;
        }

        .doctor-info {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .book-btn {
            margin-top: auto;
            padding: 12px;
            background-color: #7260ff;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            text-transform: uppercase;
            box-shadow: 0 4px 8px rgba(114, 96, 255, 0.3);
            transition: all 0.3s ease;
        }

        .book-btn:hover {
            background-color: #5d4ceb;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(114, 96, 255, 0.4);
        }

       
        .doctors-grid::-webkit-scrollbar {
            width: 10px;
            border-radius: 5px;
        }

        .doctors-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 5px;
        }

        .doctors-grid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 5px;
        }

        .doctors-grid::-webkit-scrollbar-thumb:hover {
            background: #7260ff;
        }

       
        .availability-warning {
            margin-top: 5px;
            padding: 8px 12px;
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
            font-size: 14px;
        }

        .availability-checking {
            margin-top: 5px;
            padding: 8px 12px;
            background-color: #cce5ff;
            color: #004085;
            border-left: 4px solid #004085;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</body>

</html>