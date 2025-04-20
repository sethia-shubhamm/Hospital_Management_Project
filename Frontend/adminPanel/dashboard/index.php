<?php
session_start();

// Function to log errors
function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    $_SESSION['login_error'] = "Please log in as an administrator to access this page";
    header("Location: ../../adminLogin/index.php");
    exit();
}

// Include database connection
require_once '../../../db_connect.php';

// Get admin information using LoginID
$login_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$adminID = $login_id; // Default value

// Check if Admins table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Admins'");
if (mysqli_num_rows($tableCheck) == 0) {
    logError("Admins table does not exist");
    // Create Admins table
    $createAdminsTable = "CREATE TABLE Admins (
        AdminID INT PRIMARY KEY,
        AdminName VARCHAR(100) NOT NULL,
        AdminRole VARCHAR(50) NOT NULL
    )";
    if (!mysqli_query($conn, $createAdminsTable)) {
        logError("Failed to create Admins table: " . mysqli_error($conn));
    }
}

// First, check if the admin exists for this login ID
$admin_check_query = "SELECT * FROM Admins WHERE AdminID = '$login_id'";
$admin_result = mysqli_query($conn, $admin_check_query);

if (!$admin_result) {
    logError("Admin query failed: " . mysqli_error($conn));
    $admin = array('AdminID' => $login_id, 'AdminName' => 'Admin User', 'AdminRole' => 'System Administrator');
} else if (mysqli_num_rows($admin_result) > 0) {
    $admin = mysqli_fetch_assoc($admin_result);
    $adminID = $admin['AdminID'];
} else {
    // If admin record doesn't exist yet, create one based on login information
    $user_query = "SELECT * FROM LoginCredentials WHERE LoginID = '$login_id'";
    $user_result = mysqli_query($conn, $user_query);

    if (!$user_result) {
        logError("User query failed: " . mysqli_error($conn));
        $user = null;
    } else {
        $user = mysqli_fetch_assoc($user_result);
    }

    // Create a default admin record
    $create_admin_query = "INSERT INTO Admins (AdminID, AdminName, AdminRole) 
                         VALUES ('$login_id', 'Admin User', 'System Administrator')";
    if (!mysqli_query($conn, $create_admin_query)) {
        logError("Failed to create admin record: " . mysqli_error($conn));
    }

    // Fetch the newly created admin
    $admin_result = mysqli_query($conn, $admin_check_query);
    if (!$admin_result) {
        logError("Failed to fetch newly created admin: " . mysqli_error($conn));
        $admin = array('AdminID' => $login_id, 'AdminName' => 'Admin User', 'AdminRole' => 'System Administrator');
    } else {
        $admin = mysqli_fetch_assoc($admin_result);
        $adminID = $admin['AdminID'];
    }
}

// Check if Patients table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Patients'");
if (mysqli_num_rows($tableCheck) == 0) {
    logError("Patients table does not exist");
    $patientCount = 0;
    $newPatientsThisWeek = 0;
} else {
    // Count total patients
    $patientCountQuery = "SELECT COUNT(*) as total FROM Patients";
    $patientCountResult = mysqli_query($conn, $patientCountQuery);
    if (!$patientCountResult) {
        logError("Patient count query failed: " . mysqli_error($conn));
        $patientCount = 0;
    } else {
        $patientCount = mysqli_fetch_assoc($patientCountResult)['total'];
    }

    // Get new patients this week
    $oneWeekAgo = date('Y-m-d', strtotime('-7 days'));

    // First check if RegisterDate column exists in Patients table
    $checkRegisterDateQuery = "SHOW COLUMNS FROM Patients LIKE 'RegisterDate'";
    $checkRegisterDateResult = mysqli_query($conn, $checkRegisterDateQuery);

    if ($checkRegisterDateResult && mysqli_num_rows($checkRegisterDateResult) > 0) {
        // RegisterDate column exists, use it in the query
        $newPatientsQuery = "SELECT COUNT(*) as total FROM Patients WHERE RegisterDate >= '$oneWeekAgo'";
    } else {
        // Try alternative column names or use a default static value
        $checkCreatedAtQuery = "SHOW COLUMNS FROM Patients LIKE 'CreatedAt'";
        $checkCreatedAtResult = mysqli_query($conn, $checkCreatedAtQuery);

        if ($checkCreatedAtResult && mysqli_num_rows($checkCreatedAtResult) > 0) {
            $newPatientsQuery = "SELECT COUNT(*) as total FROM Patients WHERE CreatedAt >= '$oneWeekAgo'";
        } else {
            // If we can't determine new patients by date, just show a default value
            $newPatientsThisWeek = 5; // Default value
            $newPatientsQuery = null;
        }
    }

    if ($newPatientsQuery) {
        $newPatientsResult = mysqli_query($conn, $newPatientsQuery);
        if (!$newPatientsResult) {
            logError("New patients query failed: " . mysqli_error($conn));
            $newPatientsThisWeek = 0;
        } else {
            $newPatientsThisWeek = mysqli_fetch_assoc($newPatientsResult)['total'];
        }
    }
}

// Check if Doctors table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Doctors'");
if (mysqli_num_rows($tableCheck) == 0) {
    logError("Doctors table does not exist");
    $doctorCount = 0;
    $newDoctorsThisMonth = 0;
} else {
    // Count total doctors
    $doctorCountQuery = "SELECT COUNT(*) as total FROM Doctors";
    $doctorCountResult = mysqli_query($conn, $doctorCountQuery);
    if (!$doctorCountResult) {
        logError("Doctor count query failed: " . mysqli_error($conn));
        $doctorCount = 0;
    } else {
        $doctorCount = mysqli_fetch_assoc($doctorCountResult)['total'];
    }

    // Get new doctors this month
    $oneMonthAgo = date('Y-m-d', strtotime('-1 month'));

    // First check if JoinDate column exists in Doctors table
    $checkJoinDateQuery = "SHOW COLUMNS FROM Doctors LIKE 'JoinDate'";
    $checkJoinDateResult = mysqli_query($conn, $checkJoinDateQuery);

    if ($checkJoinDateResult && mysqli_num_rows($checkJoinDateResult) > 0) {
        // JoinDate column exists, use it in the query
        $newDoctorsQuery = "SELECT COUNT(*) as total FROM Doctors WHERE JoinDate >= '$oneMonthAgo'";
    } else {
        // Try alternative column names or use a default static value
        $checkCreatedAtQuery = "SHOW COLUMNS FROM Doctors LIKE 'CreatedAt'";
        $checkCreatedAtResult = mysqli_query($conn, $checkCreatedAtQuery);

        if ($checkCreatedAtResult && mysqli_num_rows($checkCreatedAtResult) > 0) {
            $newDoctorsQuery = "SELECT COUNT(*) as total FROM Doctors WHERE CreatedAt >= '$oneMonthAgo'";
        } else {
            // If we can't determine new doctors by date, just show a default value
            $newDoctorsThisMonth = 3; // Default value
            $newDoctorsQuery = null;
        }
    }

    if ($newDoctorsQuery) {
        $newDoctorsResult = mysqli_query($conn, $newDoctorsQuery);
        if (!$newDoctorsResult) {
            logError("New doctors query failed: " . mysqli_error($conn));
            $newDoctorsThisMonth = 0;
        } else {
            $newDoctorsThisMonth = mysqli_fetch_assoc($newDoctorsResult)['total'];
        }
    }
}

// Check if Appointments table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Appointments'");
if (mysqli_num_rows($tableCheck) == 0) {
    logError("Appointments table does not exist");
    $apptCount = 0;
    $apptTodayCount = 0;
    $recentApptResult = null;
} else {
    // Count total appointments
    $apptCountQuery = "SELECT COUNT(*) as total FROM Appointments";
    $apptCountResult = mysqli_query($conn, $apptCountQuery);
    if (!$apptCountResult) {
        logError("Appointment count query failed: " . mysqli_error($conn));
        $apptCount = 0;
    } else {
        $apptCount = mysqli_fetch_assoc($apptCountResult)['total'];
    }

    // Count today's appointments
    $today = date('Y-m-d');
    $apptTodayQuery = "SELECT COUNT(*) as total FROM Appointments WHERE AppointmentDate = '$today'";
    $apptTodayResult = mysqli_query($conn, $apptTodayQuery);
    if (!$apptTodayResult) {
        logError("Today's appointments query failed: " . mysqli_error($conn));
        $apptTodayCount = 0;
    } else {
        $apptTodayCount = mysqli_fetch_assoc($apptTodayResult)['total'];
    }

    // Check for column names in Appointments table
    $columnsQuery = "SHOW COLUMNS FROM Appointments";
    $columnsResult = mysqli_query($conn, $columnsQuery);
    $patientIdColumn = 'PatientID';
    $doctorIdColumn = 'DoctorID';
    $appointmentDateColumn = 'AppointmentDate';
    $appointmentTimeColumn = 'AppointmentTime';
    $appointmentStatusColumn = 'Status';

    if ($columnsResult) {
        $columns = array();
        while ($column = mysqli_fetch_assoc($columnsResult)) {
            $columns[] = $column['Field'];
        }

        // Check for patient_id vs PatientID
        if (in_array('patient_id', $columns)) {
            $patientIdColumn = 'patient_id';
        }

        // Check for doctor_id vs DoctorID
        if (in_array('doctor_id', $columns)) {
            $doctorIdColumn = 'doctor_id';
        }

        // Check for appointment_date vs AppointmentDate
        if (in_array('appointment_date', $columns)) {
            $appointmentDateColumn = 'appointment_date';
        }

        // Check for appointment_time vs AppointmentTime
        if (in_array('appointment_time', $columns)) {
            $appointmentTimeColumn = 'appointment_time';
        }

        // Check for status vs Status
        if (in_array('status', $columns)) {
            $appointmentStatusColumn = 'status';
        }
    } else {
        logError("Failed to get columns from Appointments table: " . mysqli_error($conn));
    }

    // Get recent appointments with dynamic column names
    $recentApptQuery = "SELECT a.*, p.PatientName, d.DoctorName 
                      FROM Appointments a 
                      JOIN Patients p ON a.$patientIdColumn = p.PatientID 
                      JOIN Doctors d ON a.$doctorIdColumn = d.DoctorID 
                      ORDER BY a.$appointmentDateColumn DESC, a.$appointmentTimeColumn DESC 
                      LIMIT 5";
    $recentApptResult = mysqli_query($conn, $recentApptQuery);

    if (!$recentApptResult) {
        logError("Recent appointments query failed: " . mysqli_error($conn));
        $recentApptResult = null;
    }
}

// Calculate estimated revenue (example calculation)
$avgAppointmentCost = 150; // Average cost per appointment in dollars
$estimatedRevenue = $apptCount * $avgAppointmentCost;
$revenueTrend = "+8%"; // Example trend

// Get recent activities (simulated data)
$recentActivities = [
    ['type' => 'doctor', 'text' => 'Dr. John Smith added to Cardiology department', 'time' => '2 hours ago'],
    ['type' => 'patient', 'text' => 'New patient Emily Johnson registered', 'time' => '3 hours ago'],
    ['type' => 'appointment', 'text' => $apptTodayCount . ' new appointments scheduled for today', 'time' => '5 hours ago'],
    ['type' => 'system', 'text' => 'System maintenance completed successfully', 'time' => 'Yesterday']
];

// Format functions
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
    <title>Admin Dashboard | Seattle Grace Hospital</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li class="active">
                    <a href="index.php">
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
                <li>
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
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1>Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <input type="text" placeholder="Search...">
                        <button><i class="bi bi-search"></i></button>
                    </div>
                    <div class="admin-profile">
                        
                        <div class="profile-info">
                            <span class="name"><?php echo htmlspecialchars($admin['AdminName']); ?></span>
                            <span class="role"><?php echo htmlspecialchars($admin['AdminRole']); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Overview Cards -->
                <section class="overview-cards">
                    <div class="card doctors-card">
                        <div class="card-icon">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="card-info">
                            <h3>Doctors</h3>
                            <span class="value"><?php echo $doctorCount; ?></span>
                            <span class="change positive">+<?php echo $newDoctorsThisMonth; ?> this month</span>
                        </div>
                    </div>

                    <div class="card patients-card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-info">
                            <h3>Patients</h3>
                            <span class="value"><?php echo $patientCount; ?></span>
                            <span class="change positive">+<?php echo $newPatientsThisWeek; ?> this week</span>
                        </div>
                    </div>

                    <div class="card appointments-card">
                        <div class="card-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="card-info">
                            <h3>Appointments</h3>
                            <span class="value"><?php echo $apptCount; ?></span>
                            <span class="change positive">+<?php echo $apptTodayCount; ?> today</span>
                        </div>
                    </div>

                    <div class="card revenue-card">
                        <div class="card-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="card-info">
                            <h3>Revenue</h3>
                            <span class="value">$<?php echo number_format($estimatedRevenue); ?></span>
                            <span class="change positive"><?php echo $revenueTrend; ?> this month</span>
                        </div>
                    </div>
                </section>

                <!-- Statistics Chart -->
                <section class="statistics-section">
                    <div class="chart-card">
                        <div class="card-header">
                            <h3>Hospital Statistics</h3>
                            <div class="controls">
                                <select id="chartPeriod">
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                    <option value="year">This Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-container">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div style="position: relative; height: 250px; width: 100%;">
                                        <canvas id="appointmentsChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div style="position: relative; height: 250px; width: 100%;">
                                        <canvas id="patientsChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div style="position: relative; height: 250px; width: 100%;">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Activity -->
                <section class="recent-activities">
                    <div class="activity-card">
                        <div class="card-header">
                            <h3>Recent Activities</h3>
                            <a href="#" class="view-all">View All</a>
                        </div>
                        <div class="activity-list">
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?php echo $activity['type']; ?>">
                                        <i
                                            class="fas fa-<?php echo $activity['type'] === 'doctor' ? 'user-md' :
                                                ($activity['type'] === 'patient' ? 'user' :
                                                    ($activity['type'] === 'appointment' ? 'calendar-check' : 'cog')); ?>"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p><?php echo htmlspecialchars($activity['text']); ?></p>
                                        <span class="time"><?php echo htmlspecialchars($activity['time']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="dashboard-tables">
                    <!-- Recent Appointments -->
                    <div class="table-card">
                        <div class="card-header">
                            <h3>Recent Appointments</h3>
                            <a href="../appointments/index.php" class="view-all">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($recentApptResult && mysqli_num_rows($recentApptResult) > 0):
                                        while ($appt = mysqli_fetch_assoc($recentApptResult)):
                                            $patientInitials = substr($appt['PatientName'], 0, 1) .
                                                (strpos($appt['PatientName'], ' ') ? substr($appt['PatientName'], strpos($appt['PatientName'], ' ') + 1, 1) : '');
                                            $status = isset($appt[$appointmentStatusColumn]) ? $appt[$appointmentStatusColumn] : 'Scheduled';
                                            $statusClass = strtolower($status) === 'confirmed' ? 'confirmed' :
                                                (strtolower($status) === 'cancelled' ? 'cancelled' :
                                                    (strtolower($status) === 'completed' ? 'completed' : 'pending'));
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="user-avatar"><?php echo $patientInitials; ?></div>
                                                        <span><?php echo htmlspecialchars($appt['PatientName']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($appt['DoctorName']); ?></td>
                                                <td><?php echo formatDate($appt[$appointmentDateColumn]); ?></td>
                                                <td><?php echo formatTime($appt[$appointmentTimeColumn]); ?></td>
                                                <td><span
                                                        class="status <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                                </td>
                                            </tr>
                                            <?php
                                        endwhile;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No recent appointments found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Doctor Stats -->
                    <div class="table-card">
                        <div class="card-header">
                            <h3>Top Doctors</h3>
                            <a href="../doctors/index.php" class="view-all">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Specialty</th>
                                        <th>Patients</th>
                                        <th>Availability</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get top doctors by appointment count
                                    $topDoctorsQuery = "SELECT d.DoctorID, d.DoctorName, d.Specialty, 
                                                       COUNT(a.$patientIdColumn) as patientCount
                                                       FROM Doctors d
                                                       LEFT JOIN Appointments a ON d.DoctorID = a.$doctorIdColumn
                                                       GROUP BY d.DoctorID
                                                       ORDER BY patientCount DESC
                                                       LIMIT 5";
                                    $topDoctorsResult = mysqli_query($conn, $topDoctorsQuery);

                                    if ($topDoctorsResult && mysqli_num_rows($topDoctorsResult) > 0):
                                        while ($doctor = mysqli_fetch_assoc($topDoctorsResult)):
                                            $doctorInitials = substr($doctor['DoctorName'], 0, 1) .
                                                (strpos($doctor['DoctorName'], ' ') ? substr($doctor['DoctorName'], strpos($doctor['DoctorName'], ' ') + 1, 1) : '');
                                            // Example availability status - this would be calculated based on schedules
                                            $availability = rand(0, 1) ? 'Available' : 'Busy';
                                            $availabilityClass = $availability === 'Available' ? 'available' : 'unavailable';
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="user-info">
                                                        <div class="user-avatar doctor"><?php echo $doctorInitials; ?></div>
                                                        <span><?php echo htmlspecialchars($doctor['DoctorName']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($doctor['Specialty']); ?></td>
                                                <td><?php echo $doctor['patientCount']; ?></td>
                                                <td><span
                                                        class="status <?php echo $availabilityClass; ?>"><?php echo $availability; ?></span>
                                                </td>
                                            </tr>
                                            <?php
                                        endwhile;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No doctors found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Sidebar toggle functionality
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');

            if (sidebarCollapse) {
                sidebarCollapse.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }

            // Mobile responsive behavior
            function checkWidth() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('active');
                } else {
                    sidebar.classList.remove('active');
                }
            }

            // Initial check
            checkWidth();

            // Listen for window resize
            window.addEventListener('resize', checkWidth);

            // Chart.js initialization
            const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
            const patientsCtx = document.getElementById('patientsChart').getContext('2d');
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');

            // Sample data for appointments by day of week
            const appointmentsData = {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Appointments',
                    data: [12, 19, 15, 22, 18, 10, 5],
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgb(52, 152, 219)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            };

            // Sample data for patients by category
            const patientsData = {
                labels: ['Outpatient', 'Inpatient', 'Emergency', 'Surgery', 'Specialist'],
                datasets: [{
                    label: 'Patients',
                    data: [45, 25, 15, 10, 5],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.5)',
                        'rgba(52, 152, 219, 0.5)',
                        'rgba(231, 76, 60, 0.5)',
                        'rgba(241, 196, 15, 0.5)',
                        'rgba(142, 68, 173, 0.5)'
                    ],
                    borderColor: [
                        'rgb(46, 204, 113)',
                        'rgb(52, 152, 219)',
                        'rgb(231, 76, 60)',
                        'rgb(241, 196, 15)',
                        'rgb(142, 68, 173)'
                    ],
                    borderWidth: 1
                }]
            };

            // Create appointments line chart
            const appointmentsChart = new Chart(appointmentsCtx, {
                type: 'line',
                data: appointmentsData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Weekly Appointments'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Create patients pie chart
            const patientsChart = new Chart(patientsCtx, {
                type: 'doughnut',
                data: patientsData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Patient Distribution'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Revenue data
            const revenueData = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [15000, 18000, 16500, 21000, 22500, 25000],
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderColor: 'rgb(46, 204, 113)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Expenses ($)',
                    data: [12000, 13500, 14000, 15000, 16000, 17500],
                    backgroundColor: 'rgba(231, 76, 60, 0.2)',
                    borderColor: 'rgb(231, 76, 60)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            };

            // Create revenue chart
            const revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: revenueData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Revenue vs Expenses (Last 6 Months)'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function (value) {
                                    return '$' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Handle chart period selector
            document.getElementById('chartPeriod').addEventListener('change', function () {
                // In a real app, this would fetch new data based on the selected period
                // For this demo, we'll just update chart titles

                const period = this.value;
                let newAppointmentsData;
                let newRevenueData = {};

                switch (period) {
                    case 'week':
                        // Update appointments chart
                        appointmentsChart.options.plugins.title.text = 'Weekly Appointments';
                        newAppointmentsData = [12, 19, 15, 22, 18, 10, 5];
                        appointmentsChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

                        // Update revenue chart
                        revenueChart.options.plugins.title.text = 'Revenue vs Expenses (This Week)';
                        revenueChart.data.labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        newRevenueData.revenue = [3000, 4200, 3800, 5000, 4500, 3200, 2000];
                        newRevenueData.expenses = [2500, 3000, 2800, 3500, 3200, 2200, 1800];
                        break;

                    case 'month':
                        // Update appointments chart
                        appointmentsChart.options.plugins.title.text = 'Monthly Appointments';
                        newAppointmentsData = [48, 55, 42, 65];
                        appointmentsChart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];

                        // Update revenue chart
                        revenueChart.options.plugins.title.text = 'Revenue vs Expenses (This Month)';
                        revenueChart.data.labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                        newRevenueData.revenue = [15000, 18000, 16500, 21000];
                        newRevenueData.expenses = [12000, 13500, 14000, 15000];
                        break;

                    case 'year':
                        // Update appointments chart
                        appointmentsChart.options.plugins.title.text = 'Yearly Appointments';
                        newAppointmentsData = [250, 320, 280, 305, 270, 290, 310, 285, 295, 320, 340, 310];
                        appointmentsChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                        // Update revenue chart
                        revenueChart.options.plugins.title.text = 'Revenue vs Expenses (This Year)';
                        revenueChart.data.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        newRevenueData.revenue = [45000, 52000, 48000, 53000, 56000, 60000, 58000, 62000, 65000, 68000, 72000, 75000];
                        newRevenueData.expenses = [38000, 42000, 40000, 43000, 45000, 47000, 46000, 48000, 51000, 53000, 55000, 58000];
                        break;
                }

                // Update appointment chart data
                appointmentsChart.data.datasets[0].data = newAppointmentsData;
                appointmentsChart.update();

                // Update revenue chart data
                revenueChart.data.datasets[0].data = newRevenueData.revenue;
                revenueChart.data.datasets[1].data = newRevenueData.expenses;
                revenueChart.update();
            });
        });
    </script>
</body>

</html>