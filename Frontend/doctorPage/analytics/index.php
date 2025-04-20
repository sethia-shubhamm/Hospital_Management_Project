<?php
session_start();
require_once '../../../db_connect.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Doctor') {
    $_SESSION['login_error'] = "Please log in as a doctor to access this page";
    header("Location: ../../doctorLogin/index.php");
    exit();
}


include_once('setup_advanced_sql.php');


$doctor_id = $_SESSION['user_id'];


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


$check_procedure = "SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name = 'get_doctor_stats'";
$procedure_result = mysqli_query($conn, $check_procedure);

if (mysqli_num_rows($procedure_result) == 0) {
    
    $create_procedure = "
    CREATE PROCEDURE get_doctor_stats(IN doctor_id INT)
    BEGIN
        -- Get appointment count by status
        SELECT 
            Status,
            COUNT(*) as count
        FROM Appointments
        WHERE DoctorID = doctor_id
        GROUP BY Status;
        
        -- Get appointment count by month for the last 6 months
        SELECT 
            DATE_FORMAT(AppointmentDate, '%Y-%m') as month,
            COUNT(*) as count
        FROM Appointments
        WHERE DoctorID = doctor_id
        AND AppointmentDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month;
        
        -- Get most common patient conditions
        SELECT 
            Reason as condition,
            COUNT(*) as count
        FROM Appointments
        WHERE DoctorID = doctor_id
        GROUP BY Reason
        ORDER BY count DESC
        LIMIT 5;
        
        -- Get patient demographics
        SELECT 
            TIMESTAMPDIFF(YEAR, p.DateOfBirth, CURDATE()) DIV 10 * 10 as age_group,
            COUNT(*) as patient_count
        FROM Appointments a
        JOIN Patients p ON a.PatientID = p.PatientID
        WHERE a.DoctorID = doctor_id
        GROUP BY age_group
        ORDER BY age_group;
    END
    ";

    if (!mysqli_query($conn, $create_procedure)) {
        logError("Failed to create stored procedure: " . mysqli_error($conn));
    }
}


$doctor_query = "SELECT * FROM Doctors WHERE DoctorID = '$doctor_id'";
$doctor_result = mysqli_query($conn, $doctor_query);
$doctor = mysqli_fetch_assoc($doctor_result);


$appointment_summary = "
SELECT 
    a.Status,
    COUNT(a.AppointmentID) as count,
    COUNT(DISTINCT a.PatientID) as unique_patients
FROM Appointments a
WHERE a.DoctorID = '$doctor_id'
GROUP BY a.Status
";
$appointment_result = mysqli_query($conn, $appointment_summary);


$patient_history = "
SELECT 
    p.PatientID,
    CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
    COUNT(a.AppointmentID) as visit_count,
    MAX(a.AppointmentDate) as last_visit,
    GROUP_CONCAT(DISTINCT a.Reason SEPARATOR ', ') as conditions
FROM Patients p
JOIN Appointments a ON p.PatientID = a.PatientID
WHERE a.DoctorID = '$doctor_id'
GROUP BY p.PatientID, PatientName
ORDER BY visit_count DESC
LIMIT 10
";
$history_result = mysqli_query($conn, $patient_history);


$efficiency_query = "SELECT calculate_doctor_efficiency($doctor_id) AS efficiency_score";
$efficiency_result = mysqli_query($conn, $efficiency_query);
$efficiency_row = mysqli_fetch_assoc($efficiency_result);
$efficiency_score = round($efficiency_row['efficiency_score'], 1);


$start_date = date('Y-m-d', strtotime('-3 months'));
$end_date = date('Y-m-d');
$call_procedure = "CALL generate_patient_report($doctor_id, '$start_date', '$end_date')";
$procedure_success = mysqli_multi_query($conn, $call_procedure);


$patient_report = [];
$report_stats = [];
$condition_stats = [];

if ($procedure_success) {
    
    $result = mysqli_store_result($conn);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $patient_report[] = $row;
        }
        mysqli_free_result($result);
    }

    
    if (mysqli_next_result($conn)) {
        $result = mysqli_store_result($conn);
        if ($result) {
            $report_stats = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
        }
    }

    
    if (mysqli_next_result($conn)) {
        $result = mysqli_store_result($conn);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $condition_stats[] = $row;
            }
            mysqli_free_result($result);
        }
    }
}


$view_query = "SELECT * FROM DoctorAnalyticsView WHERE DoctorID = $doctor_id";
$view_result = mysqli_query($conn, $view_query);
$analytics_data = mysqli_fetch_assoc($view_result);


$history_query = "
SELECT 
    a.AppointmentID,
    CONCAT(p.FirstName, ' ', p.LastName) AS PatientName,
    h.OldStatus,
    h.NewStatus,
    h.ChangedAt
FROM AppointmentStatusHistory h
JOIN Appointments a ON h.AppointmentID = a.AppointmentID
JOIN Patients p ON a.PatientID = p.PatientID
WHERE a.DoctorID = $doctor_id
ORDER BY h.ChangedAt DESC
LIMIT 10
";
$history_result = mysqli_query($conn, $history_query);
$status_history = [];
if ($history_result) {
    while ($row = mysqli_fetch_assoc($history_result)) {
        $status_history[] = $row;
    }
}


$performance_query = "
SELECT 
    CONCAT(d.FirstName, ' ', d.LastName) as DoctorName,
    d.Specialization,
    COUNT(a.AppointmentID) as total_appointments,
    AVG(appointment_counts.monthly_count) as avg_monthly_appointments,
    d.DoctorID = '$doctor_id' as is_current_doctor
FROM Doctors d
JOIN Appointments a ON d.DoctorID = a.DoctorID
JOIN (
    SELECT 
        DoctorID,
        DATE_FORMAT(AppointmentDate, '%Y-%m') as month,
        COUNT(*) as monthly_count
    FROM Appointments
    WHERE AppointmentDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DoctorID, month
) as appointment_counts ON d.DoctorID = appointment_counts.DoctorID
WHERE d.Specialization = (SELECT Specialization FROM Doctors WHERE DoctorID = '$doctor_id')
GROUP BY d.DoctorID, DoctorName, d.Specialization, is_current_doctor
ORDER BY total_appointments DESC
";
$performance_result = mysqli_query($conn, $performance_query);


$months = [];
$month_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));
    $month_data[$month] = 0;
}


$monthly_query = "
SELECT 
    DATE_FORMAT(AppointmentDate, '%Y-%m') as month,
    COUNT(*) as count
FROM Appointments
WHERE DoctorID = '$doctor_id'
AND AppointmentDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY month
ORDER BY month
";
$monthly_result = mysqli_query($conn, $monthly_query);
while ($row = mysqli_fetch_assoc($monthly_result)) {
    $month_data[$row['month']] = $row['count'];
}
$appointment_counts = array_values($month_data);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Analytics | Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            background-color: #f5f8fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            padding: 20px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eaeaea;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-body {
            padding: 20px;
        }

        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            color: white;
            height: 100%;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 1rem;
            margin-bottom: 0;
        }

        .bg-primary-gradient {
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
        }

        .bg-success-gradient {
            background: linear-gradient(45deg, #198754, #157347);
        }

        .bg-warning-gradient {
            background: linear-gradient(45deg, #ffc107, #ffca2c);
        }

        .bg-info-gradient {
            background: linear-gradient(45deg, #0dcaf0, #0bb8da);
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            white-space: nowrap;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .highlight {
            background-color: #e7f3ff;
            font-weight: 600;
        }

        .filter-section {
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Include Header/Navbar -->
    <?php include_once '../../components/navbar.php'; ?>

    <div class="container-fluid dashboard-container">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3">Analytics Dashboard</h1>
                <p class="text-muted">Welcome, Dr. <?php echo $doctor['FirstName'] . ' ' . $doctor['LastName']; ?></p>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="row mb-4">
            <?php
            $status_colors = [
                'Completed' => 'bg-success-gradient',
                'Scheduled' => 'bg-primary-gradient',
                'Cancelled' => 'bg-warning-gradient',
                'Pending' => 'bg-info-gradient'
            ];

            while ($row = mysqli_fetch_assoc($appointment_result)) {
                $status = $row['Status'];
                $count = $row['count'];
                $color = isset($status_colors[$status]) ? $status_colors[$status] : 'bg-secondary';
                ?>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card h-100">
                        <div class="stat-card <?php echo $color; ?>">
                            <h3><?php echo $count; ?></h3>
                            <p><?php echo $status; ?> Appointments</p>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Monthly Appointments</span>
                    </div>
                    <div class="card-body">
                        <canvas id="appointmentChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Top Patients</span>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Visits</th>
                                        <th>Last Visit</th>
                                        <th>Conditions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($history_result) > 0) {
                                        while ($row = mysqli_fetch_assoc($history_result)) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['PatientName']) . "</td>";
                                            echo "<td>" . $row['visit_count'] . "</td>";
                                            echo "<td>" . date('M d, Y', strtotime($row['last_visit'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['conditions']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center'>No patient data available</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Comparison -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <span>Performance Comparison (Same Specialty)</span>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>Specialty</th>
                                        <th>Total Appointments</th>
                                        <th>Avg. Monthly</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($performance_result) > 0) {
                                        while ($row = mysqli_fetch_assoc($performance_result)) {
                                            $highlight = $row['is_current_doctor'] ? 'highlight' : '';
                                            echo "<tr class='$highlight'>";
                                            echo "<td>" . htmlspecialchars($row['DoctorName']) . ($row['is_current_doctor'] ? ' (You)' : '') . "</td>";
                                            echo "<td>" . htmlspecialchars($row['Specialization']) . "</td>";
                                            echo "<td>" . $row['total_appointments'] . "</td>";
                                            echo "<td>" . number_format($row['avg_monthly_appointments'], 1) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center'>No comparison data available</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Efficiency Score Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <span>Doctor Efficiency Score</span>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6 text-center">
                                <div class="efficiency-meter">
                                    <div class="progress" style="height: 30px;">
                                        <?php
                                        $efficiency_class = 'bg-danger';
                                        if ($efficiency_score >= 70) {
                                            $efficiency_class = 'bg-success';
                                        } elseif ($efficiency_score >= 40) {
                                            $efficiency_class = 'bg-warning';
                                        }
                                        ?>
                                        <div class="progress-bar <?php echo $efficiency_class; ?>" role="progressbar"
                                            style="width: <?php echo $efficiency_score; ?>%;"
                                            aria-valuenow="<?php echo $efficiency_score; ?>" aria-valuemin="0"
                                            aria-valuemax="100">
                                            <?php echo $efficiency_score; ?>%
                                        </div>
                                    </div>
                                    <p class="mt-2 text-muted">Based on appointment completion rate and cancellations
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4>Your Performance Metrics</h4>
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td>Total Appointments</td>
                                            <td><?php echo isset($analytics_data['TotalAppointments']) ? $analytics_data['TotalAppointments'] : 0; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Completed Appointments</td>
                                            <td><?php echo isset($analytics_data['CompletedAppointments']) ? $analytics_data['CompletedAppointments'] : 0; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Unique Patients</td>
                                            <td><?php echo isset($analytics_data['UniquePatients']) ? $analytics_data['UniquePatients'] : 0; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Avg. Booking Lead Time</td>
                                            <td><?php echo isset($analytics_data['AvgBookingLeadTimeDays']) ? round($analytics_data['AvgBookingLeadTimeDays'], 1) : 0; ?>
                                                days</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status History Section (from trigger) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <span>Appointment Status Changes</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($status_history)): ?>
                            <p class="text-center">No status changes recorded yet.</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Old Status</th>
                                            <th>New Status</th>
                                            <th>Changed At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($status_history as $history): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($history['PatientName']); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo $history['OldStatus']; ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badge_class = 'bg-secondary';
                                                    if ($history['NewStatus'] == 'Completed') {
                                                        $badge_class = 'bg-success';
                                                    } elseif ($history['NewStatus'] == 'Scheduled') {
                                                        $badge_class = 'bg-primary';
                                                    } elseif ($history['NewStatus'] == 'Cancelled') {
                                                        $badge_class = 'bg-danger';
                                                    }
                                                    ?>
                                                    <span
                                                        class="badge <?php echo $badge_class; ?>"><?php echo $history['NewStatus']; ?></span>
                                                </td>
                                                <td><?php echo date('M d, Y H:i', strtotime($history['ChangedAt'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient Report Section (from stored procedure with cursor) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>3-Month Patient Report</span>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($start_date)); ?> -
                            <?php echo date('M d, Y', strtotime($end_date)); ?></small>
                    </div>
                    <div class="card-body">
                        <?php if (empty($patient_report)): ?>
                            <p class="text-center">No patient data available for this period.</p>
                        <?php else: ?>
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3><?php echo isset($report_stats['TotalPatients']) ? $report_stats['TotalPatients'] : 0; ?>
                                            </h3>
                                            <p>Total Patients</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3><?php echo isset($report_stats['AverageVisitsPerPatient']) ? round($report_stats['AverageVisitsPerPatient'], 1) : 0; ?>
                                            </h3>
                                            <p>Avg. Visits/Patient</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3><?php echo isset($report_stats['MaxVisits']) ? $report_stats['MaxVisits'] : 0; ?>
                                            </h3>
                                            <p>Max Visits</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3><?php echo isset($report_stats['MinVisits']) ? $report_stats['MinVisits'] : 0; ?>
                                            </h3>
                                            <p>Min Visits</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">Patient Details</h5>
                            <div class="table-container">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Visits</th>
                                            <th>Last Visit</th>
                                            <th>Common Conditions</th>
                                            <th>Treatment Summary</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($patient_report as $patient): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($patient['PatientName']); ?></td>
                                                <td><?php echo $patient['VisitCount']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($patient['LastVisitDate'])); ?></td>
                                                <td><?php echo htmlspecialchars($patient['CommonConditions']); ?></td>
                                                <td><?php echo htmlspecialchars($patient['TreatmentSummary']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (!empty($condition_stats)): ?>
                                <h5 class="mt-4 mb-3">Top Conditions</h5>
                                <div class="row">
                                    <?php foreach (array_slice($condition_stats, 0, 4) as $condition): ?>
                                        <div class="col-md-3 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($condition['Condition']); ?>
                                                    </h5>
                                                    <h6 class="card-subtitle mb-2 text-muted">
                                                        <?php echo $condition['Occurrences']; ?> occurrences</h6>
                                                    <p class="card-text">
                                                        <small><?php echo $condition['UniquePatients']; ?> patients</small><br>
                                                        <small><?php echo round($condition['PercentageOfTotal'], 1); ?>% of
                                                            total</small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include_once '../../components/footer.php'; ?>

    <!-- Bootstrap & jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        const ctx = document.getElementById('appointmentChart').getContext('2d');
        const appointmentChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode($appointment_counts); ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    tension: 0.2,
                    fill: true,
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        padding: 10,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 14
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>