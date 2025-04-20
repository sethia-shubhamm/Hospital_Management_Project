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


$login_id = $_SESSION['user_id'];
$email = $_SESSION['email'];


$doctorsTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Doctors'");
if (mysqli_num_rows($doctorsTableCheck) == 0) {
    logError("Doctors table does not exist, creating it");
    
    $createDoctorsTable = "CREATE TABLE Doctors (
        DoctorID INT PRIMARY KEY,
        DoctorName VARCHAR(100) NOT NULL,
        Specialty VARCHAR(100) NULL
    )";
    if (!mysqli_query($conn, $createDoctorsTable)) {
        logError("Failed to create Doctors table: " . mysqli_error($conn));
    }
}


$doctor_check_query = "SELECT * FROM Doctors WHERE DoctorID = '$login_id'";
$doctor_result = mysqli_query($conn, $doctor_check_query);

if (!$doctor_result) {
    logError("Database error checking doctor: " . mysqli_error($conn));
}

if (mysqli_num_rows($doctor_result) > 0) {
    $doctor = mysqli_fetch_assoc($doctor_result);
    $doctorID = $doctor['DoctorID'];
    $doctorName = $doctor['DoctorName'];
    $specialty = $doctor['Specialty'];
} else {
    
    $user_query = "SELECT * FROM LoginCredentials WHERE LoginID = '$login_id'";
    $user_result = mysqli_query($conn, $user_query);

    if (!$user_result) {
        logError("Database error getting login credentials: " . mysqli_error($conn));
    }

    $user = mysqli_fetch_assoc($user_result);
    $email_parts = explode('@', $email);
    $default_name = 'Dr. ' . ucfirst($email_parts[0]);

    
    $create_doctor_query = "INSERT INTO Doctors (DoctorID, DoctorName, Specialty) 
                          VALUES ('$login_id', '$default_name', 'General Physician')";
    $create_result = mysqli_query($conn, $create_doctor_query);

    if (!$create_result) {
        logError("Database error creating doctor: " . mysqli_error($conn));
    }

    
    $doctor_result = mysqli_query($conn, $doctor_check_query);
    if (!$doctor_result) {
        logError("Database error fetching new doctor: " . mysqli_error($conn));
    }
    $doctor = mysqli_fetch_assoc($doctor_result);
    $doctorID = $doctor['DoctorID'];
    $doctorName = $doctor['DoctorName'];
    $specialty = $doctor['Specialty'];
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


$patients_columns_query = "SHOW COLUMNS FROM Patients";
$patients_columns_result = mysqli_query($conn, $patients_columns_query);

if (!$patients_columns_result) {
    logError("Database error checking Patients columns: " . mysqli_error($conn));
}


$has_gender = false;
$has_age = false;
$has_blood_type = false;
$blood_type_column = '';

if ($patients_columns_result) {
    
    $column_names = [];
    while ($column = mysqli_fetch_assoc($patients_columns_result)) {
        $col_name = strtolower($column['Field']);
        $column_names[] = $col_name;

        if ($col_name === 'gender')
            $has_gender = true;
        if ($col_name === 'age')
            $has_age = true;

        
        if ($col_name === 'bloodtype') {
            $has_blood_type = true;
            $blood_type_column = 'BloodType';
        } elseif ($col_name === 'blood_type') {
            $has_blood_type = true;
            $blood_type_column = 'blood_type AS BloodType';
        } elseif ($col_name === 'blood') {
            $has_blood_type = true;
            $blood_type_column = 'blood AS BloodType';
        }
    }
}


$apptQuery = "SELECT a.*, p.PatientName 
              FROM Appointments a 
              JOIN Patients p ON a.$patientIdColumn = p.PatientID
              WHERE a.$doctorIdColumn = '$doctorID' AND a.$appointmentDateColumn = '$today' 
              ORDER BY a.$appointmentTimeColumn";
$apptResult = mysqli_query($conn, $apptQuery);

if (!$apptResult) {
    logError("Database error fetching appointments: " . mysqli_error($conn));
    $apptResult = false; 
}


$upcomingApptQuery = "SELECT a.*, p.PatientName 
                    FROM Appointments a 
                    JOIN Patients p ON a.$patientIdColumn = p.PatientID
                    WHERE a.$doctorIdColumn = '$doctorID' 
                    AND a.$appointmentDateColumn >= '$today' 
                    ORDER BY a.$appointmentDateColumn, a.$appointmentTimeColumn 
                    LIMIT 8";

$upcomingApptResult = mysqli_query($conn, $upcomingApptQuery);

if (!$upcomingApptResult) {
    logError("Database error fetching upcoming appointments: " . mysqli_error($conn));
    $upcomingApptResult = false;
}


$patientCountQuery = "SELECT COUNT(DISTINCT $patientIdColumn) as total 
                     FROM Appointments 
                     WHERE $doctorIdColumn = '$doctorID'";
$patientCountResult = mysqli_query($conn, $patientCountQuery);

if (!$patientCountResult) {
    logError("Database error counting patients: " . mysqli_error($conn));
    $patientCount = 0; 
} else {
    $patientCount = mysqli_fetch_assoc($patientCountResult)['total'];
}


$upcomingQuery = "SELECT COUNT(*) as total 
                 FROM Appointments 
                 WHERE $doctorIdColumn = '$doctorID' AND $appointmentDateColumn >= '$today'";
$upcomingResult = mysqli_query($conn, $upcomingQuery);

if (!$upcomingResult) {
    logError("Database error counting upcoming appointments: " . mysqli_error($conn));
    $upcomingCount = 0; 
} else {
    $upcomingCount = mysqli_fetch_assoc($upcomingResult)['total'];
}


function formatDate($dateStr)
{
    return date('l, F j, Y', strtotime($dateStr));
}


function formatTime($timeStr)
{
    return date('h:i A', strtotime($timeStr));
}


$today_formatted = formatDate($today);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, width=device-width">
    <title>Doctor Dashboard | Hospital Management System</title>
    <style>
        :root {
            --primary-color: #7260ff;
            --secondary-color: #644dff;
            --text-color: #333;
            --bg-color: #f5f7fb;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

       
        body {
            min-height: 100vh;
            display: flex;
        }

       
        .logo-container {
            display: none;
           
        }

       
        .top-navbar {
            position: fixed;
            top: 0;
            right: 0;
            width: calc(98% - var(--sidebar-width));
           
            height: 60px;
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 90;
            margin-left: var(--sidebar-width);
        }

        .top-nav-links {
            display: flex;
            gap: 30px;
        }

        .top-nav-links a {
            color: #555;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .top-nav-links a:hover,
        .top-nav-links a.active {
            color: #7260ff;
        }

       
        .choiceSection {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background-color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 80;
        }

        .logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 40px;
        }

        .logo img {
            width: 40px;
            height: 40px;
        }

        .logo h6 {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .menu-items {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .menu-items div {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #555;
            gap: 8px;
            transition: color 0.2s;
            cursor: pointer;
        }

        .menu-items div img {
            width: 24px;
            height: 24px;
            opacity: 0.7;
        }

        .menu-items div h6 {
            font-size: 12px;
            font-weight: 500;
        }

        .menu-items div:hover {
            color: #7260ff;
        }

        .menu-items div:hover img {
            opacity: 1;
        }

        .menu-items div.active {
            color: #7260ff;
        }

        .menu-items div.active img {
            opacity: 1;
        }

        .logout {
            margin-top: auto;
            color: #ff4747;
        }

        .logout a {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #ff4747;
            gap: 8px;
            transition: color 0.2s;
        }

        .logout a:hover {
            color: #ff4747;
        }

        .logout a img {
            width: 24px;
            height: 24px;
        }

        .logout a h6 {
            font-size: 12px;
            font-weight: 500;
        }

        .content-area {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
        }

       
        .content-container {
            margin-left: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
        }

        .main-content {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .welcome-section {
            margin-bottom: 30px;
        }

        .welcome-section h1 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .welcome-section p {
            color: #777;
            font-size: 14px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
        }

        .stat-card img {
            width: 40px;
            height: 40px;
            margin-right: 15px;
        }

        .stat-number {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .stat-label {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }

        .section-heading {
            color: #333;
            font-size: 18px;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .appointments-container {
            margin-bottom: 30px;
        }

        .appointment-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .appointment-item h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .appointment-item p {
            font-size: 14px;
            color: #777;
            margin-bottom: 5px;
        }

        .appointment-item span {
            font-size: 12px;
            color: #7260ff;
            font-weight: 500;
        }

        .no-appointments {
            padding: 20px;
            text-align: center;
            background: #f9f9f9;
            border-radius: 8px;
            color: #777;
            font-size: 14px;
        }

       
        .appointment-card {
            border-radius: 12px;
            border: none;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .appointment-card .card-body {
            padding: 16px;
        }

        .appointment-card h5 {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }

        .appointment-card .text-muted {
            font-size: 12px;
        }

        .appointment-card .badge {
            font-size: 11px;
            font-weight: 500;
            padding: 5px 8px;
        }

        .appointment-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #eee;
        }

        .appointment-details p {
            font-size: 13px;
            margin-bottom: 8px;
        }

        .appointment-details strong {
            color: #555;
        }

        .btn-outline-primary {
            color: #7260ff;
            border-color: #7260ff;
        }

        .btn-outline-primary:hover {
            background-color: #7260ff;
            border-color: #7260ff;
        }

        .bg-primary {
            background-color: #7260ff !important;
        }

       
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" />
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Top Navigation -->
    <div class="top-navbar">
        <div class="top-nav-links">
            <a href="../../index.php" class="active">HOME</a>
            <a href="../../allDoctors/index.php">ALL DOCTORS</a>
            <a href="../../bloodBank/index.php">BLOOD BANK</a>
            <a href="../../index.php#contact">CONTACT</a>
        </div>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser1"
                data-bs-toggle="dropdown" aria-expanded="false">
                <span>Dr. <?php echo htmlspecialchars(str_replace('Dr. ', '', $doctorName)); ?></span>
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

    <!-- Side Navigation -->
    <div class="choiceSection">
        <div>
            <div class="logo">
                <img src="icons/logo.png" alt="Hospital Logo">
                <h6>Seattle Grace Hospital</h6>
            </div>

            <div class="menu-items">
                <div class="active" onclick="window.location.href='index.php'">
                    <img src="icons/dashboard.png" alt="dashboard">
                    <h6>Dashboard</h6>
                </div>
                <div onclick="window.location.href='../appointments/index.php'">
                    <img src="icons/appointment.png" alt="appointments">
                    <h6>Appointments</h6>
                </div>
                <div onclick="window.location.href='../patients/index.php'">
                    <img src="icons/patient.png" alt="patients">
                    <h6>Patients</h6>
                </div>
                <div onclick="window.location.href='../profile/index.php'">
                    <img src="icons/profile.png" alt="profile">
                    <h6>Profile</h6>
                </div>
                <div onclick="window.location.href='../medicalRecords/view_medical_records.php'">
                        <img src="icons\medicine.png" alt="Medical Records">
                        <h6>Medical Records</h6>
                    </div>
            </div>
        </div>
        <div class="logout">
            <a href="../../logout.php">
                <img src="icons/Logout.png" alt="logout">
                <h6>Logout</h6>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-area">
        <div class="main-content">
            <div class="welcome-section">
                <h1>Welcome back, Dr. <?php echo htmlspecialchars(str_replace('Dr. ', '', $doctorName)); ?></h1>
                <p><?php echo $today_formatted; ?></p>
            </div>

            <div class="stats-container">
                <div class="stat-card">
                    <img src="icons/patient.png" alt="">
                    <div>
                        <div class="stat-number"><?php echo $patientCount; ?></div>
                        <div class="stat-label">Total Patients</div>
                    </div>
                </div>
                <div class="stat-card">
                    <img src="icons/appointment.png" alt="">
                    <div>
                        <div class="stat-number"><?php echo $upcomingCount; ?></div>
                        <div class="stat-label">Upcoming Appointments</div>
                    </div>
                </div>
                <div class="stat-card">
                    <img src="icons/medicine.png" alt="">
                    <div>
                        <div class="stat-number"><?php echo htmlspecialchars($specialty); ?></div>
                        <div class="stat-label">Specialty</div>
                    </div>
                </div>
            </div>

            <h2 class="section-heading">Today's Appointments</h2>
            <div class="appointments-container">
                <?php if ($apptResult && mysqli_num_rows($apptResult) > 0): ?>
                    <div class="row">
                        <?php while ($appt = mysqli_fetch_assoc($apptResult)): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card appointment-card shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-0"><?php echo htmlspecialchars($appt['PatientName']); ?></h5>
                                                <small class="text-muted">
                                                    Today, <?php echo formatTime($appt[$appointmentTimeColumn]); ?>
                                                </small>
                                            </div>
                                            <?php
                                            $status = isset($appt['Status']) ? $appt['Status'] : 'Scheduled';
                                            $statusClass = 'bg-primary';
                                            if (strtolower($status) == 'confirmed')
                                                $statusClass = 'bg-success';
                                            if (strtolower($status) == 'cancelled')
                                                $statusClass = 'bg-danger';
                                            if (strtolower($status) == 'completed')
                                                $statusClass = 'bg-info';
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                        </div>

                                        <div class="appointment-details">
                                            <p class="mb-1">
                                                <strong>Purpose:</strong>
                                                <?php echo $appt['AppointmentPurpose'] ?? ($appt['Purpose'] ?? 'Regular Checkup'); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <?php
                                                $duration = isset($appt['Duration']) ? $appt['Duration'] : 30;
                                                $time = strtotime($appt[$appointmentTimeColumn]);
                                                $endTime = date('h:i A', strtotime("+{$duration} minutes", $time));
                                                ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo formatTime($appt[$appointmentTimeColumn]); ?> -
                                                    <?php echo $endTime; ?>
                                                </small>
                                                <a href="../appointments/index.php"
                                                    class="btn btn-sm btn-outline-primary">View</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-appointments">
                        <p>No appointments scheduled for today.</p>
                    </div>
                <?php endif; ?>
            </div>

            <h2 class="section-heading">Upcoming Schedule</h2>
            <div class="appointments-container">
                <?php if ($upcomingApptResult && mysqli_num_rows($upcomingApptResult) > 0): ?>
                    <div class="row">
                        <?php while ($upAppt = mysqli_fetch_assoc($upcomingApptResult)): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card appointment-card shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h5 class="mb-0"><?php echo htmlspecialchars($upAppt['PatientName']); ?></h5>
                                                <small class="text-muted">
                                                    <?php
                                                    
                                                    $appt_date = date('l, M j', strtotime($upAppt[$appointmentDateColumn]));
                                                    echo $appt_date . ', ' . formatTime($upAppt[$appointmentTimeColumn]);
                                                    ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-primary">Scheduled</span>
                                        </div>

                                        <div class="appointment-details">
                                            <p class="mb-1">
                                                <strong>Purpose:</strong>
                                                <?php
                                                if (isset($upAppt[$appointmentPurposeColumn]) && !empty($upAppt[$appointmentPurposeColumn]) && $upAppt[$appointmentPurposeColumn] != 'NULL') {
                                                    echo htmlspecialchars($upAppt[$appointmentPurposeColumn]);
                                                } else {
                                                    echo 'Regular Checkup';
                                                }
                                                ?>
                                            </p>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i>
                                                    Appointment #<?php echo $upAppt[$appointmentIDColumn]; ?>
                                                </small>
                                                <a href="../appointments/index.php"
                                                    class="btn btn-sm btn-outline-primary">Details</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-appointments">
                        <p>No upcoming appointments found. The database shows appointments, but they may not match the
                            current doctor ID.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>

</html>