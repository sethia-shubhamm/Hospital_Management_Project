<?php
session_start();


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    $_SESSION['login_error'] = "Please log in as an administrator to access this page";
    header("Location: ../../adminLogin/index.php");
    exit();
}


require_once '../../../db_connect.php';


$login_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$adminID = $login_id; 


$admin_check_query = "SELECT * FROM Admins WHERE AdminID = '$login_id'";
$admin_result = mysqli_query($conn, $admin_check_query);

if (!$admin_result) {
    logError("Admin query failed: " . mysqli_error($conn));
    $admin = array('AdminID' => $login_id, 'AdminName' => 'Admin User', 'AdminRole' => 'System Administrator');
} else if (mysqli_num_rows($admin_result) > 0) {
    $admin = mysqli_fetch_assoc($admin_result);
    $adminID = $admin['AdminID'];
} else {
    
    $admin = array('AdminID' => $login_id, 'AdminName' => 'Admin User', 'AdminRole' => 'System Administrator');
}


$columnsQuery = "SHOW COLUMNS FROM Appointments";
$columnsResult = mysqli_query($conn, $columnsQuery);
$patientIdColumn = 'PatientID';
$doctorIdColumn = 'DoctorID';
$appointmentDateColumn = 'AppointmentDate';
$appointmentTimeColumn = 'AppointmentTime';
$appointmentStatusColumn = 'Status';
$appointmentIDColumn = 'AppointmentID';

if ($columnsResult) {
    $columns = array();
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        $columns[] = $column['Field'];
    }

    
    if (in_array('patient_id', $columns)) {
        $patientIdColumn = 'patient_id';
    }

    
    if (in_array('doctor_id', $columns)) {
        $doctorIdColumn = 'doctor_id';
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

    
    if (in_array('id', $columns)) {
        $appointmentIDColumn = 'id';
    }
} else {
    logError("Failed to get columns from Appointments table: " . mysqli_error($conn));
}


$today = date('Y-m-d');
$todayAppointmentsQuery = "SELECT a.*, p.PatientName, d.DoctorName 
                          FROM Appointments a 
                          JOIN Patients p ON a.$patientIdColumn = p.PatientID 
                          JOIN Doctors d ON a.$doctorIdColumn = d.DoctorID 
                          WHERE a.$appointmentDateColumn = '$today' 
                          ORDER BY a.$appointmentTimeColumn";
$todayAppointmentsResult = mysqli_query($conn, $todayAppointmentsQuery);

if (!$todayAppointmentsResult) {
    logError("Today's appointments query failed: " . mysqli_error($conn));
    $todayAppointmentsResult = null;
}


$upcomingAppointmentsQuery = "SELECT a.*, p.PatientName, d.DoctorName 
                             FROM Appointments a 
                             JOIN Patients p ON a.$patientIdColumn = p.PatientID 
                             JOIN Doctors d ON a.$doctorIdColumn = d.DoctorID 
                             WHERE a.$appointmentDateColumn > '$today' 
                             ORDER BY a.$appointmentDateColumn, a.$appointmentTimeColumn 
                             LIMIT 10";
$upcomingAppointmentsResult = mysqli_query($conn, $upcomingAppointmentsQuery);

if (!$upcomingAppointmentsResult) {
    logError("Upcoming appointments query failed: " . mysqli_error($conn));
    $upcomingAppointmentsResult = null;
}


$pastAppointmentsQuery = "SELECT a.*, p.PatientName, d.DoctorName 
                         FROM Appointments a 
                         JOIN Patients p ON a.$patientIdColumn = p.PatientID 
                         JOIN Doctors d ON a.$doctorIdColumn = d.DoctorID 
                         WHERE a.$appointmentDateColumn < '$today' 
                         ORDER BY a.$appointmentDateColumn DESC, a.$appointmentTimeColumn DESC 
                         LIMIT 10";
$pastAppointmentsResult = mysqli_query($conn, $pastAppointmentsQuery);

if (!$pastAppointmentsResult) {
    logError("Past appointments query failed: " . mysqli_error($conn));
    $pastAppointmentsResult = null;
}


function formatDate($dateStr)
{
    return date('M d, Y', strtotime($dateStr));
}

function formatTime($timeStr)
{
    return date('h:i A', strtotime($timeStr));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Hospital Logo" class="logo"> 
                <h3>Admin Panel</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="../dashboard/index.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../doctors/index.php">
                        <i class="bi bi-person-badge"></i>
                        <span>Doctors</span>
                    </a>
                </li>
                <li>
                    <a href="../patients/index.php">
                        <i class="bi bi-people"></i>
                        <span>Patients</span>
                    </a>
                </li>
                <li class="active">
                    <a href="../appointments/index.php">
                        <i class="bi bi-calendar-check"></i>
                        <span>Appointments</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../logout.php" class="logout">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content" style="margin-left: 275px;">
            <header class="content-header">
                <div class="header-left">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 style="margin-right: 550px;">Appointments</h1>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <input type="text" placeholder="Search appointments...">
                        <button><i class="bi bi-search"></i></button>
                    </div>
                    <div class="admin-profile">
                        <img src="../assets/img/logo.png" alt="Admin Profile">
                        <div class="profile-info">
                            <span class="name"><?php echo htmlspecialchars($admin['AdminName']); ?></span>
                            <span class="role"><?php echo htmlspecialchars($admin['AdminRole']); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['bill_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> <?php echo $_SESSION['bill_success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['bill_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['bill_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> <?php echo $_SESSION['bill_error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['bill_error']); ?>
            <?php endif; ?>

            <div class="content-header-actions">
                <div>
                    <h2>Manage Appointments</h2>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#newAppointmentModal">
                    <i class="bi bi-plus-circle"></i> New Appointment
                </button>
            </div>

            <div class="filters-bar">
                <div class="filter-group">
                    <label>Filter by Date:</label>
                    <input type="date" class="form-control">
                </div>
                <div class="filter-group">
                    <label>Doctor:</label>
                    <select class="form-control">
                        <option value="">All Doctors</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status:</label>
                    <select class="form-control">
                        <option value="">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <button class="btn btn-filter">
                    <i class="bi bi-funnel"></i> Apply Filters
                </button>
            </div>

            <!-- Today's Appointments -->
            <section class="appointments-section">
                <h3 class="section-title">Today's Appointments</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($todayAppointmentsResult && mysqli_num_rows($todayAppointmentsResult) > 0): ?>
                                <?php while ($appointment = mysqli_fetch_assoc($todayAppointmentsResult)): ?>
                                    <tr>
                                        <td>#<?php echo $appointment[$appointmentIDColumn]; ?></td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?php
                                                    $patientInitials = substr($appointment['PatientName'], 0, 1) .
                                                        (strpos($appointment['PatientName'], ' ') ? substr($appointment['PatientName'], strpos($appointment['PatientName'], ' ') + 1, 1) : '');
                                                    echo $patientInitials;
                                                    ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($appointment['PatientName']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['DoctorName']); ?></td>
                                        <td><?php echo formatTime($appointment[$appointmentTimeColumn]); ?></td>
                                        <td>
                                            <?php
                                            $status = isset($appointment[$appointmentStatusColumn]) ? $appointment[$appointmentStatusColumn] : 'Scheduled';
                                            $statusClass = strtolower($status) === 'confirmed' ? 'confirmed' :
                                                (strtolower($status) === 'cancelled' ? 'cancelled' :
                                                    (strtolower($status) === 'completed' ? 'completed' : 'pending'));
                                            ?>
                                            <span class="status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td class="actions">
                                            <button class="action-btn view" data-bs-toggle="modal"
                                                data-bs-target="#viewDetailsModal"
                                                data-appointment-id="<?php echo $appointment[$appointmentIDColumn]; ?>"
                                                data-patient-name="<?php echo htmlspecialchars($appointment['PatientName']); ?>"
                                                data-doctor-name="<?php echo htmlspecialchars($appointment['DoctorName']); ?>"
                                                data-date="<?php echo formatDate($appointment[$appointmentDateColumn]); ?>"
                                                data-time="<?php echo formatTime($appointment[$appointmentTimeColumn]); ?>"
                                                data-status="<?php echo $status; ?>" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="action-btn text-success generate-bill" data-bs-toggle="tooltip"
                                                title="Generate Bill"
                                                data-appointment-id="<?php echo $appointment[$appointmentIDColumn]; ?>"
                                                data-patient-id="<?php echo $appointment[$patientIdColumn]; ?>"
                                                data-patient-name="<?php echo htmlspecialchars($appointment['PatientName']); ?>"
                                                data-bs-target="#generateBillModal" data-bs-toggle="modal">
                                                <i class="bi bi-receipt"></i>
                                            </button>
                                            <a href="delete_appointment.php?id=<?php echo $appointment[$appointmentIDColumn]; ?>"
                                                class="action-btn delete"
                                                onclick="return confirm('Are you sure you want to delete this appointment?')"
                                                data-bs-toggle="tooltip" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No appointments scheduled for today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Upcoming Appointments -->
            <section class="appointments-section">
                <h3 class="section-title">Upcoming Appointments</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($upcomingAppointmentsResult && mysqli_num_rows($upcomingAppointmentsResult) > 0): ?>
                                <?php while ($appointment = mysqli_fetch_assoc($upcomingAppointmentsResult)): ?>
                                    <tr>
                                        <td>#<?php echo $appointment[$appointmentIDColumn]; ?></td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?php
                                                    $patientInitials = substr($appointment['PatientName'], 0, 1) .
                                                        (strpos($appointment['PatientName'], ' ') ? substr($appointment['PatientName'], strpos($appointment['PatientName'], ' ') + 1, 1) : '');
                                                    echo $patientInitials;
                                                    ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($appointment['PatientName']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['DoctorName']); ?></td>
                                        <td><?php echo formatDate($appointment[$appointmentDateColumn]); ?></td>
                                        <td><?php echo formatTime($appointment[$appointmentTimeColumn]); ?></td>
                                        <td>
                                            <?php
                                            $status = isset($appointment[$appointmentStatusColumn]) ? $appointment[$appointmentStatusColumn] : 'Scheduled';
                                            $statusClass = strtolower($status) === 'confirmed' ? 'confirmed' :
                                                (strtolower($status) === 'cancelled' ? 'cancelled' :
                                                    (strtolower($status) === 'completed' ? 'completed' : 'pending'));
                                            ?>
                                            <span class="status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td class="actions">
                                            <button class="action-btn view" data-bs-toggle="modal"
                                                data-bs-target="#viewDetailsModal"
                                                data-appointment-id="<?php echo $appointment[$appointmentIDColumn]; ?>"
                                                data-patient-name="<?php echo htmlspecialchars($appointment['PatientName']); ?>"
                                                data-doctor-name="<?php echo htmlspecialchars($appointment['DoctorName']); ?>"
                                                data-date="<?php echo formatDate($appointment[$appointmentDateColumn]); ?>"
                                                data-time="<?php echo formatTime($appointment[$appointmentTimeColumn]); ?>"
                                                data-status="<?php echo $status; ?>" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="action-btn text-success generate-bill" data-bs-toggle="tooltip"
                                                title="Generate Bill"
                                                data-appointment-id="<?php echo $appointment[$appointmentIDColumn]; ?>"
                                                data-patient-id="<?php echo $appointment[$patientIdColumn]; ?>"
                                                data-patient-name="<?php echo htmlspecialchars($appointment['PatientName']); ?>"
                                                data-bs-target="#generateBillModal" data-bs-toggle="modal">
                                                <i class="bi bi-receipt"></i>
                                            </button>
                                            <a href="delete_appointment.php?id=<?php echo $appointment[$appointmentIDColumn]; ?>"
                                                class="action-btn delete"
                                                onclick="return confirm('Are you sure you want to delete this appointment?')"
                                                data-bs-toggle="tooltip" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No upcoming appointments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Past Appointments -->
            <section class="appointments-section">
                <h3 class="section-title">Past Appointments</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pastAppointmentsResult && mysqli_num_rows($pastAppointmentsResult) > 0): ?>
                                <?php while ($appointment = mysqli_fetch_assoc($pastAppointmentsResult)): ?>
                                    <tr>
                                        <td>#<?php echo $appointment[$appointmentIDColumn]; ?></td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?php
                                                    $patientInitials = substr($appointment['PatientName'], 0, 1) .
                                                        (strpos($appointment['PatientName'], ' ') ? substr($appointment['PatientName'], strpos($appointment['PatientName'], ' ') + 1, 1) : '');
                                                    echo $patientInitials;
                                                    ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($appointment['PatientName']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['DoctorName']); ?></td>
                                        <td><?php echo formatDate($appointment[$appointmentDateColumn]); ?></td>
                                        <td>
                                            <?php
                                            $status = isset($appointment[$appointmentStatusColumn]) ? $appointment[$appointmentStatusColumn] : 'Completed';
                                            $statusClass = strtolower($status) === 'confirmed' ? 'confirmed' :
                                                (strtolower($status) === 'cancelled' ? 'cancelled' :
                                                    (strtolower($status) === 'completed' ? 'completed' : 'pending'));
                                            ?>
                                            <span class="status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td class="actions">
                                            <button class="action-btn view" data-bs-toggle="modal"
                                                data-bs-target="#viewDetailsModal"
                                                data-appointment-id="<?php echo $appointment[$appointmentIDColumn]; ?>"
                                                data-patient-name="<?php echo htmlspecialchars($appointment['PatientName']); ?>"
                                                data-doctor-name="<?php echo htmlspecialchars($appointment['DoctorName']); ?>"
                                                data-date="<?php echo formatDate($appointment[$appointmentDateColumn]); ?>"
                                                data-time="<?php echo formatTime($appointment[$appointmentTimeColumn]); ?>"
                                                data-status="<?php echo $status; ?>" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="action-btn text-success generate-bill" data-bs-toggle="tooltip"
                                                title="Generate Bill"
                                                data-appointment-id="<?php echo $appointment[$appointmentIDColumn]; ?>"
                                                data-patient-id="<?php echo $appointment[$patientIdColumn]; ?>"
                                                data-patient-name="<?php echo htmlspecialchars($appointment['PatientName']); ?>"
                                                data-bs-target="#generateBillModal" data-bs-toggle="modal">
                                                <i class="bi bi-receipt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No past appointments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="pagination">
                <button class="pagination-btn prev"><i class="bi bi-chevron-left"></i></button>
                <button class="pagination-btn active">1</button>
                <button class="pagination-btn">2</button>
                <button class="pagination-btn">3</button>
                <span class="pagination-ellipsis">...</span>
                <button class="pagination-btn">10</button>
                <button class="pagination-btn next"><i class="bi bi-chevron-right"></i></button>
            </div>
        </main>
    </div>

    <!-- New Appointment Modal -->
    <div class="modal fade" id="newAppointmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newAppointmentForm">
                        <div class="mb-3">
                            <label for="patientSelect" class="form-label">Patient</label>
                            <select class="form-select" id="patientSelect" required>
                                <option value="" selected disabled>Select patient</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="doctorSelect" class="form-label">Doctor</label>
                            <select class="form-select" id="doctorSelect" required>
                                <option value="" selected disabled>Select doctor</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="appointmentDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentTime" class="form-label">Time</label>
                            <input type="time" class="form-control" id="appointmentTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentType" class="form-label">Appointment Type</label>
                            <select class="form-select" id="appointmentType">
                                <option value="Consultation">Consultation</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Check-up">Check-up</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentStatus" class="form-label">Status</label>
                            <select class="form-select" id="appointmentStatus">
                                <option value="Scheduled">Scheduled</option>
                                <option value="Confirmed">Confirmed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="appointmentNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="appointmentNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Schedule Appointment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Appointment Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Appointment ID</label>
                            <p id="viewAppointmentId" class="mb-0"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <p id="viewStatus" class="mb-0"><span class="status"></span></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Patient</label>
                            <p id="viewPatientName" class="mb-0"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Doctor</label>
                            <p id="viewDoctorName" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date</label>
                            <p id="viewDate" class="mb-0"></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Time</label>
                            <p id="viewTime" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="row mt-4" id="viewActionButtons">
                        <div class="col-12 d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-primary btn-sm view-patient-profile">
                                <i class="bi bi-person"></i> Patient Profile
                            </button>
                            <button type="button" class="btn btn-info btn-sm view-doctor-profile">
                                <i class="bi bi-person-badge"></i> Doctor Profile
                            </button>
                            <button type="button" class="btn btn-success btn-sm generate-bill-btn">
                                <i class="bi bi-receipt"></i> Generate Bill
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Bill Modal -->
    <div class="modal fade" id="generateBillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Patient Bill</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="generateBillForm" action="generate_bill.php" method="post">
                        <input type="hidden" id="billAppointmentId" name="appointment_id">
                        <input type="hidden" id="billPatientId" name="patient_id">

                        <div class="mb-3">
                            <label class="form-label">Patient Name</label>
                            <input type="text" class="form-control" id="billPatientName" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="billAmount" class="form-label">Bill Amount (₹)</label>
                            <input type="number" class="form-control" id="billAmount" name="bill_amount" required
                                min="1" step="0.01">
                            <div class="invalid-feedback">Please enter a valid amount</div>
                        </div>

                        <div class="mb-3">
                            <label for="billDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="billDescription" name="bill_description" rows="3"
                                required></textarea>
                            <div class="invalid-feedback">Please provide a description</div>
                        </div>

                        <div class="mb-3">
                            <label for="billType" class="form-label">Bill Type</label>
                            <select class="form-select" id="billType" name="bill_type" required>
                                <option value="" selected disabled>Select bill type</option>
                                <option value="Consultation">Consultation</option>
                                <option value="Treatment">Treatment</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Laboratory">Laboratory Tests</option>
                                <option value="Surgery">Surgery</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select a bill type</div>
                        </div>

                        <div class="mb-3">
                            <label for="billStatus" class="form-label">Payment Status</label>
                            <select class="form-select" id="billStatus" name="bill_status" required>
                                <option value="Pending">Pending</option>
                                <option value="Paid">Paid</option>
                                <option value="Partially Paid">Partially Paid</option>
                            </select>
                        </div>

                        <div class="mb-3" id="partialPaymentDiv" style="display: none;">
                            <label for="partialAmount" class="form-label">Paid Amount (₹)</label>
                            <input type="number" class="form-control" id="partialAmount" name="partial_amount" min="0"
                                step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="generateBillForm" class="btn btn-primary">Generate Bill</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');

            if (sidebarCollapse) {
                sidebarCollapse.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }

            
            function checkWidth() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('active');
                } else {
                    sidebar.classList.remove('active');
                }
            }

            
            checkWidth();

            
            window.addEventListener('resize', checkWidth);

            
            const generateBillButtons = document.querySelectorAll('.generate-bill');
            generateBillButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const appointmentId = this.getAttribute('data-appointment-id');
                    const patientId = this.getAttribute('data-patient-id');
                    const patientName = this.getAttribute('data-patient-name');

                    
                    document.getElementById('generateBillForm').reset();

                    
                    document.getElementById('billAppointmentId').value = appointmentId;
                    document.getElementById('billPatientId').value = patientId;
                    document.getElementById('billPatientName').value = patientName;

                    
                    document.getElementById('partialPaymentDiv').style.display = 'none';

                    
                    new bootstrap.Modal(document.getElementById('generateBillModal')).show();
                });
            };

            
            const viewButtons = document.querySelectorAll('.view');
            viewButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const appointmentId = this.getAttribute('data-appointment-id');
                    const patientName = this.getAttribute('data-patient-name');
                    const doctorName = this.getAttribute('data-doctor-name');
                    const date = this.getAttribute('data-date');
                    const time = this.getAttribute('data-time');
                    const status = this.getAttribute('data-status');

                    document.getElementById('viewAppointmentId').textContent = '#' + appointmentId;
                    document.getElementById('viewPatientName').textContent = patientName;
                    document.getElementById('viewDoctorName').textContent = doctorName;
                    document.getElementById('viewDate').textContent = date;
                    document.getElementById('viewTime').textContent = time;

                    const statusSpan = document.querySelector('#viewStatus .status');
                    statusSpan.textContent = status;
                    statusSpan.className = 'status';

                    const statusClass = status.toLowerCase() === 'confirmed' ? 'confirmed' :
                        (status.toLowerCase() === 'cancelled' ? 'cancelled' :
                            (status.toLowerCase() === 'completed' ? 'completed' :
                                (status.toLowerCase() === 'billed' ? 'completed' : 'pending')));
                    statusSpan.classList.add(statusClass);

                    
                    const generateBillBtn = document.querySelector('.generate-bill-btn');
                    generateBillBtn.setAttribute('data-appointment-id', appointmentId);
                    const viewPatientBtn = document.querySelector('.view-patient-profile');
                    viewPatientBtn.onclick = function () {
                        window.location.href = '../patients/view_patient.php?id=' + this.closest('[data-patient-id]').getAttribute('data-patient-id');
                    };
                    const viewDoctorBtn = document.querySelector('.view-doctor-profile');
                    viewDoctorBtn.onclick = function () {
                        window.location.href = '../doctors/view_doctor.php?id=' + this.closest('[data-doctor-id]').getAttribute('data-doctor-id');
                    };
                });
            });

            
            const viewDetailsGenerateBillBtn = document.querySelector('.generate-bill-btn');
            if (viewDetailsGenerateBillBtn) {
                viewDetailsGenerateBillBtn.addEventListener('click', function () {
                    const appointmentId = this.getAttribute('data-appointment-id');
                    
                    document.querySelector(`.generate-bill[data-appointment-id="${appointmentId}"]`).click();
                    
                    bootstrap.Modal.getInstance(document.getElementById('viewDetailsModal')).hide();
                });
            }

            
            const billStatus = document.getElementById('billStatus');
            const partialPaymentDiv = document.getElementById('partialPaymentDiv');

            billStatus.addEventListener('change', function () {
                if (this.value === 'Partially Paid') {
                    partialPaymentDiv.style.display = 'block';
                    document.getElementById('partialAmount').required = true;
                } else {
                    partialPaymentDiv.style.display = 'none';
                    document.getElementById('partialAmount').required = false;
                }
            });

            
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

            
            const generateBillForm = document.getElementById('generateBillForm');
            if (generateBillForm) {
                generateBillForm.classList.add('needs-validation');
                generateBillForm.noValidate = true;

                generateBillForm.addEventListener('submit', function (event) {
                    if (!this.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    
                    const appointmentId = document.getElementById('billAppointmentId').value;
                    const patientId = document.getElementById('billPatientId').value;

                    if (!appointmentId || !patientId) {
                        event.preventDefault();
                        alert('Missing appointment or patient information. Please try again.');
                    }

                    this.classList.add('was-validated');
                });
            }
        ;
    </script>
</body>

</html>