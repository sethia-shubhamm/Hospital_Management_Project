<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Doctor') {
    $_SESSION['login_error'] = "Please log in as a doctor to access this page";
    header("Location: ../../login/index.php");
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, '../error_log.txt');
}


$doctor_id = $_SESSION['user_id'];
$doctor_name = "";
$specialty = "";


$doctor_query = "SELECT * FROM Doctors WHERE DoctorID = '$doctor_id'";
$doctor_result = mysqli_query($conn, $doctor_query);

if (!$doctor_result) {
    logError("Database error fetching doctor: " . mysqli_error($conn));
} else {
    if (mysqli_num_rows($doctor_result) > 0) {
        $doctor = mysqli_fetch_assoc($doctor_result);
        $doctor_name = $doctor['DoctorName'];
        $specialty = $doctor['Specialty'];
    }
}


$table_check_query = "SHOW TABLES LIKE 'Appointments'";
$table_result = mysqli_query($conn, $table_check_query);

if (!$table_result) {
    logError("Database error checking tables: " . mysqli_error($conn));
}

$doctorIdColumn = 'DoctorID'; 
$patientIdColumn = 'PatientID'; 
$appointmentDateColumn = 'AppointmentDate';
$appointmentTimeColumn = 'AppointmentTime';
$appointmentStatusColumn = 'Status';
$appointmentPurposeColumn = 'AppointmentPurpose';
$appointmentIDColumn = 'AppointmentID';

if (mysqli_num_rows($table_result) > 0) {
    
    $column_check_query = "SHOW COLUMNS FROM Appointments";
    $column_result = mysqli_query($conn, $column_check_query);

    if (!$column_result) {
        logError("Database error checking columns: " . mysqli_error($conn));
    }

    
    $columns = [];
    while ($column = mysqli_fetch_assoc($column_result)) {
        $columns[] = strtolower($column['Field']);
    }

    if (in_array('doctor_id', $columns)) {
        $doctorIdColumn = 'doctor_id';
    }

    if (in_array('patient_id', $columns)) {
        $patientIdColumn = 'patient_id';
    }

    if (in_array('appointment_date', $columns)) {
        $appointmentDateColumn = 'appointment_date';
    }

    if (in_array('appointment_time', $columns)) {
        $appointmentTimeColumn = 'appointment_time';
    }

    if (in_array('status', $columns)) {
        $appointmentStatusColumn = 'status';
    }

    if (in_array('purpose', $columns)) {
        $appointmentPurposeColumn = 'purpose';
    } else if (in_array('appointmentpurpose', $columns)) {
        $appointmentPurposeColumn = 'appointmentpurpose';
    }
}


$today = date('Y-m-d');


$doctorIdColumn = 'DoctorID';
$patientIdColumn = 'PatientID';
$appointmentDateColumn = 'AppointmentDate';
$appointmentTimeColumn = 'AppointmentTime';
$appointmentPurposeColumn = 'AppointmentPurpose';
$appointmentIDColumn = 'AppointmentID';


$today_appt_query = "SELECT a.*, p.PatientName 
              FROM Appointments a 
              JOIN Patients p ON a.$patientIdColumn = p.PatientID
              WHERE a.$doctorIdColumn = '$doctor_id' AND a.$appointmentDateColumn = '$today' 
              ORDER BY a.$appointmentTimeColumn";

$today_appt_result = mysqli_query($conn, $today_appt_query);

if (!$today_appt_result) {
    logError("Database error fetching today's appointments: " . mysqli_error($conn) . " - Query: " . $today_appt_query);
    $today_appt_result = false;
}


$upcoming_appt_query = "SELECT a.*, p.PatientName 
                    FROM Appointments a 
                    JOIN Patients p ON a.$patientIdColumn = p.PatientID
                    WHERE a.$doctorIdColumn = '$doctor_id' 
                    AND a.$appointmentDateColumn > '$today' 
                    ORDER BY a.$appointmentDateColumn, a.$appointmentTimeColumn";
$upcoming_appt_result = mysqli_query($conn, $upcoming_appt_query);

if (!$upcoming_appt_result) {
    logError("Database error fetching upcoming appointments: " . mysqli_error($conn) . " - Query: " . $upcoming_appt_query);
    $upcoming_appt_result = false;
}


$patients_query = "SELECT * FROM Patients ORDER BY PatientName";
$patients_result = mysqli_query($conn, $patients_query);

if (!$patients_result) {
    logError("Database error fetching patients: " . mysqli_error($conn));
    $patients_result = false;
}


function formatDate($dateStr)
{
    return date('l, F j, Y', strtotime($dateStr));
}


function formatTime($timeStr)
{
    return date('h:i A', strtotime($timeStr));
}


function getStatusBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'scheduled':
            return 'bg-primary';
        case 'confirmed':
            return 'bg-success';
        case 'completed':
            return 'bg-info';
        case 'cancelled':
            return 'bg-danger';
        case 'no-show':
            return 'bg-warning';
        default:
            return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Appointments</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Fullcalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #5b56e8;
            --secondary-color: #3e398f;
            --accent-color: #6c63ff;
            --text-color: #333;
            --text-light: #666;
            --bg-color: #f5f7fa;
            --card-bg: #ffffff;
            --sidebar-width: 280px;
            --header-height: 70px;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --radius: 12px;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196f3;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .desktop {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

       
        .choiceSection {
            width: var(--sidebar-width);
            background-color: #ffffff;
            box-shadow: 0px 3px 15px rgba(0, 0, 0, 0.15);
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 100;
        }

        .logo {
            padding: 20px;
            background-color: rgba(114, 96, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .logo img {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
        }

        .logo h6 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: var(--text-color);
        }

        .menu-items {
            padding: 20px 0;
            flex-grow: 1;
        }

        .menu-items div {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .menu-items div img {
            width: 22px;
            height: 22px;
            margin-right: 15px;
        }

        .menu-items div h6 {
            margin: 0;
            font-size: 15px;
            font-weight: 500;
            color: #555;
        }

        .menu-items div:hover {
            background-color: rgba(114, 96, 255, 0.1);
        }

        .menu-items div:hover h6 {
            color: var(--primary-color);
        }

        .menu-items div.active {
            background-color: var(--primary-color);
        }

        .menu-items div.active h6 {
            color: white;
        }

        .logout {
            padding: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .logout a {
            display: flex;
            align-items: center;
            color: #ff6060;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        .logout img {
            width: 22px;
            height: 22px;
            margin-right: 15px;
        }

        .logout a:hover {
            background-color: rgba(255, 96, 96, 0.1);
        }

       
        .content-area {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
        }

       
        .navbar {
            height: var(--header-height);
            background: var(--card-bg);
            box-shadow: var(--shadow);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 5;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-link {
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
        }

       
        .fc {
            font-family: 'Inter', sans-serif;
        }

        .fc-button {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .fc-button:hover {
            background-color: var(--secondary-color) !important;
            border-color: var(--secondary-color) !important;
        }

        .fc-event {
            cursor: pointer;
            border-radius: 4px;
            border: none;
        }

        .card {
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

       
        @media (max-width: 992px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .choiceSection {
                width: 70px;
                overflow: hidden;
            }

            .menu-items div h6,
            .logout h6,
            .logo h6 {
                display: none;
            }

            .menu-items div img,
            .logout img {
                margin-right: 0;
            }

            .content-area {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }
    </style>
</head>

<body>
    <div class="desktop">
        <div class="choiceSection">
            <div>
                <div class="logo">
                    <img src="images\logo.png" alt=""> 
                    <h6>Seattle Grace Hospital</h6>
                </div>

                <div class="menu-items">
                    <div>
                        <img src="../dashboard/icons/dashboard.png" alt="Dashboard">
                        <a href="../dashboard/index.php" style="text-decoration: none;">
                            <h6>Dashboard</h6>
                        </a>
                    </div>
                    <div class="active">
                        <img src="../dashboard/icons/appointment.png" alt="Appointments">
                        <a href="../appointments/index.php" style="text-decoration: none;">
                            <h6>Appointments</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/patient.png" alt="Patients">
                        <a href="../patients/index.php" style="text-decoration: none;">
                            <h6>Patients</h6>
                        </a>
                    </div>
                    <div onclick="window.location.href='../medicalRecords/view_medical_records.php'">
                        <img src="images\medicine.png" alt="Medical Records">
                        <h6>Medical Records</h6>
                    </div>
                    <div>
                        <img src="../dashboard/icons/profile.png" alt="Profile">
                        <a href="../profile/index.php" style="text-decoration: none;">
                            <h6>Profile</h6>
                        </a>
                    </div>
                </div>
            </div>
            <div class="logout">
                <a href="../../logout.php">
                    <img src="../dashboard/icons/logout.png" alt="Logout">
                    <h6>Logout</h6>
                </a>
            </div>
        </div>

        <div class="content-area">
            <div class="navbar">
                <div class="logo">
                    <h6>Seattle Grace Hospital</h6>
                </div>
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/index.php">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../patients/index.php">MY PATIENTS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../appointments/index.php">APPOINTMENTS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../profile/index.php">PROFILE</a>
                    </li>
                </ul>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle"
                        id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="../dashboard/icons/profile.png" alt="profile" width="32" height="32"
                            class="rounded-circle me-2">
                        <span>Dr. <?php echo htmlspecialchars(str_replace('Dr. ', '', $doctor_name)); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser1">
                        <li><a class="dropdown-item" href="../profile/index.php">My Profile</a></li>
                        <li><a class="dropdown-item" href="../settings/index.php">Settings</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="container-fluid px-0">
                <!-- Toast container for notifications -->
                <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
                </div>

                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="page-title mb-0">Appointments</h4>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                <i class="fas fa-plus me-2"></i>New Appointment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content Row -->
                <div class="row">
                    <!-- Today's Appointments Section -->
                    <div class="col-lg-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Today's Appointments (<?php echo date('l, F j, Y'); ?>)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Time</th>
                                                <th>Patient</th>
                                                <th>Purpose</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($today_appt_result && mysqli_num_rows($today_appt_result) > 0): ?>
                                                <?php while ($appointment = mysqli_fetch_assoc($today_appt_result)): ?>
                                                    <?php
                                                    $status = isset($appointment[$appointmentStatusColumn]) ? $appointment[$appointmentStatusColumn] : 'Scheduled';
                                                    $time = isset($appointment[$appointmentTimeColumn]) ? formatTime($appointment[$appointmentTimeColumn]) : 'N/A';
                                                    $purpose = isset($appointment[$appointmentPurposeColumn]) ? $appointment[$appointmentPurposeColumn] : 'Consultation';
                                                    $appointmentId = isset($appointment[$appointmentIDColumn]) ? $appointment[$appointmentIDColumn] : 'N/A';
                                                    $badgeClass = getStatusBadgeClass($status);
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $time; ?></td>
                                                        <td><?php echo htmlspecialchars($appointment['PatientName']); ?></td>
                                                        <td><?php echo $purpose; ?></td>
                                                        <td><span
                                                                class="badge <?php echo $badgeClass; ?>"><?php echo $status; ?></span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary view-appointment"
                                                                data-bs-toggle="modal" data-bs-target="#appointmentDetailsModal"
                                                                data-appointment-id="<?php echo $appointmentId; ?>"
                                                                data-patient-name="<?php echo htmlspecialchars($appointment['PatientName']); ?>"
                                                                data-appointment-time="<?php echo $time; ?>"
                                                                data-appointment-purpose="<?php echo $purpose; ?>"
                                                                data-appointment-status="<?php echo $status; ?>"
                                                                data-patient-id="<?php echo $appointment[$patientIdColumn]; ?>">
                                                                <i class="fas fa-eye me-1"></i> View
                                                            </button>

                                                            <?php if (strtolower($status) == 'scheduled' || strtolower($status) == 'confirmed'): ?>
                                                                <a href="change_status.php?id=<?php echo $appointmentId; ?>&status=completed"
                                                                    class="btn btn-sm btn-success">
                                                                    <i class="fas fa-check-circle me-1"></i> Complete
                                                                </a>
                                                                <a href="cancel_appointment.php?id=<?php echo $appointmentId; ?>"
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                                    <i class="fas fa-times-circle me-1"></i> Cancel
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4">
                                                        <i class="fas fa-calendar-check fa-2x text-muted mb-3"></i>
                                                        <p class="mb-0">No appointments scheduled for today</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Appointments Section -->
                    <div class="col-lg-12 mb-4">
                        <div class="card">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Upcoming Appointments</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Patient</th>
                                                <th>Purpose</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($upcoming_appt_result && mysqli_num_rows($upcoming_appt_result) > 0): ?>
                                                <?php while ($appointment = mysqli_fetch_assoc($upcoming_appt_result)): ?>
                                                    <?php
                                                    $status = isset($appointment[$appointmentStatusColumn]) ? $appointment[$appointmentStatusColumn] : 'Scheduled';
                                                    $time = isset($appointment[$appointmentTimeColumn]) ? formatTime($appointment[$appointmentTimeColumn]) : 'N/A';
                                                    $date = isset($appointment[$appointmentDateColumn]) ? formatDate($appointment[$appointmentDateColumn]) : 'N/A';
                                                    $purpose = isset($appointment[$appointmentPurposeColumn]) ? $appointment[$appointmentPurposeColumn] : 'Consultation';
                                                    $appointmentId = isset($appointment[$appointmentIDColumn]) ? $appointment[$appointmentIDColumn] : 'N/A';
                                                    $badgeClass = getStatusBadgeClass($status);
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $date; ?></td>
                                                        <td><?php echo $time; ?></td>
                                                        <td><?php echo htmlspecialchars($appointment['PatientName']); ?></td>
                                                        <td><?php echo $purpose; ?></td>
                                                        <td><span
                                                                class="badge <?php echo $badgeClass; ?>"><?php echo $status; ?></span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary view-appointment"
                                                                data-bs-toggle="modal" data-bs-target="#appointmentDetailsModal"
                                                                data-appointment-id="<?php echo $appointmentId; ?>"
                                                                data-patient-name="<?php echo htmlspecialchars($appointment['PatientName']); ?>"
                                                                data-appointment-time="<?php echo $time; ?>"
                                                                data-appointment-date="<?php echo $date; ?>"
                                                                data-appointment-purpose="<?php echo $purpose; ?>"
                                                                data-appointment-status="<?php echo $status; ?>"
                                                                data-patient-id="<?php echo $appointment[$patientIdColumn]; ?>">
                                                                <i class="fas fa-eye me-1"></i> View
                                                            </button>

                                                            <?php if (strtolower($status) == 'scheduled' || strtolower($status) == 'confirmed'): ?>
                                                                <a href="cancel_appointment.php?id=<?php echo $appointmentId; ?>"
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                                    <i class="fas fa-times-circle me-1"></i> Cancel
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <i class="fas fa-calendar fa-2x text-muted mb-3"></i>
                                                        <p class="mb-0">No upcoming appointments scheduled</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <img id="appointmentPatientImage" src="../dashboard/icons/patient.png" alt="Patient"
                            class="rounded-circle mb-2" width="80" height="80">
                        <h5 id="appointmentPatientName">Patient Name</h5>
                        <div id="appointmentStatus" class="badge bg-primary">Status</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Appointment ID</small>
                            <span id="appointmentId">-</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Date & Time</small>
                            <span id="appointmentDateTime">-</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Appointment Type</small>
                            <span id="appointmentType">-</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Duration</small>
                            <span id="appointmentDuration">30 minutes</span>
                        </div>
                        <div class="col-12 mb-3">
                            <small class="text-muted d-block">Reason</small>
                            <span id="appointmentReason">-</span>
                        </div>
                    </div>

                    <div id="appointmentActions" class="d-flex justify-content-center gap-2 mt-3">
                        <button class="btn btn-success btn-sm" id="btnComplete">
                            <i class="fas fa-check-circle me-1"></i> Complete
                        </button>
                        <button class="btn btn-warning btn-sm" id="btnReschedule">
                            <i class="fas fa-calendar-alt me-1"></i> Reschedule
                        </button>
                        <button class="btn btn-danger btn-sm" id="btnCancel">
                            <i class="fas fa-times-circle me-1"></i> Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Appointment Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleAppointmentForm" class="needs-validation" novalidate action="save_appointment.php"
                        method="post">
                        <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                        <div class="mb-3">
                            <label for="patientSelect" class="form-label">Patient</label>
                            <select class="form-select" id="patientSelect" name="patient_id" required>
                                <option value="" selected disabled>Select patient</option>
                                <?php if ($patients_result && mysqli_num_rows($patients_result) > 0): ?>
                                    <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                                        <option value="<?php echo $patient['PatientID']; ?>">
                                            <?php echo htmlspecialchars($patient['PatientName']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">Please select a patient</div>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="appointmentDate" name="appointment_date"
                                required>
                            <div class="invalid-feedback">Please select a date</div>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentTime" class="form-label">Time</label>
                            <input type="time" class="form-control" id="appointmentTime" name="appointment_time"
                                required>
                            <div class="invalid-feedback">Please select a time</div>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentType" class="form-label">Appointment Type</label>
                            <select class="form-select" id="appointmentType" name="appointment_type" required>
                                <option value="" selected disabled>Select type</option>
                                <option value="Consultation">Consultation</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Check-up">Check-up</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                            <div class="invalid-feedback">Please select appointment type</div>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentDuration" class="form-label">Duration (minutes)</label>
                            <select class="form-select" id="appointmentDuration" name="duration" required>
                                <option value="15">15 minutes</option>
                                <option value="30" selected>30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentReason" class="form-label">Reason</label>
                            <textarea class="form-control" id="appointmentReason" name="reason" rows="3"
                                required></textarea>
                            <div class="invalid-feedback">Please provide a reason for the appointment</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.choiceSection');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('collapsed');
                });
            }

            
            const appointmentDateInput = document.getElementById('appointmentDate');
            if (appointmentDateInput) {
                const today = new Date().toISOString().split('T')[0];
                appointmentDateInput.setAttribute('min', today);
                appointmentDateInput.value = today;
            }

            
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });

            
            document.querySelectorAll('.view-appointment').forEach(button => {
                button.addEventListener('click', function () {
                    const appointmentId = this.getAttribute('data-appointment-id');
                    const patientName = this.getAttribute('data-patient-name');
                    const appointmentTime = this.getAttribute('data-appointment-time');
                    const appointmentDate = this.getAttribute('data-appointment-date') || '<?php echo date('l, F j, Y'); ?>';
                    const appointmentPurpose = this.getAttribute('data-appointment-purpose');
                    const appointmentStatus = this.getAttribute('data-appointment-status');

                    
                    document.getElementById('appointmentId').innerText = appointmentId;
                    document.getElementById('appointmentPatientName').innerText = patientName;
                    document.getElementById('appointmentDateTime').innerText = appointmentDate + ' at ' + appointmentTime;
                    document.getElementById('appointmentType').innerText = appointmentPurpose;

                    
                    const statusBadge = document.getElementById('appointmentStatus');
                    statusBadge.className = 'badge ' + getStatusBadgeClass(appointmentStatus);
                    statusBadge.innerText = appointmentStatus;

                    
                    updateActionButtons(appointmentStatus.toLowerCase(), appointmentId);
                });
            });

            
            function updateActionButtons(status, appointmentId) {
                const completeBtn = document.getElementById('btnComplete');
                const rescheduleBtn = document.getElementById('btnReschedule');
                const cancelBtn = document.getElementById('btnCancel');

                
                completeBtn.style.display = 'none';
                rescheduleBtn.style.display = 'none';
                cancelBtn.style.display = 'none';

                
                if (status === 'scheduled' || status === 'confirmed') {
                    completeBtn.style.display = 'inline-block';
                    rescheduleBtn.style.display = 'inline-block';
                    cancelBtn.style.display = 'inline-block';

                    
                    completeBtn.onclick = function () {
                        window.location.href = 'change_status.php?id=' + appointmentId + '&status=completed';
                    };
                    cancelBtn.onclick = function () {
                        if (confirm('Are you sure you want to cancel this appointment?')) {
                            window.location.href = 'cancel_appointment.php?id=' + appointmentId;
                        }
                    };
                }
            }

            
            function getStatusBadgeClass(status) {
                switch (status.toLowerCase()) {
                    case 'scheduled': return 'bg-primary';
                    case 'confirmed': return 'bg-success';
                    case 'completed': return 'bg-info';
                    case 'cancelled': return 'bg-danger';
                    case 'no-show': return 'bg-warning';
                    default: return 'bg-secondary';
                }
            }
        });
    </script>
</body>

</html>