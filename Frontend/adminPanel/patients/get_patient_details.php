<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    echo "Unauthorized access";
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>Patient ID is required</div>";
    exit();
}


$patientId = mysqli_real_escape_string($conn, $_GET['id']);


$patientQuery = "SELECT * FROM Patients WHERE PatientID = '$patientId'";
$patientResult = mysqli_query($conn, $patientQuery);

if (!$patientResult) {
    logError("Error fetching patient details: " . mysqli_error($conn));
    echo "<div class='alert alert-danger'>Error fetching patient details</div>";
    exit();
}

if (mysqli_num_rows($patientResult) === 0) {
    echo "<div class='alert alert-danger'>Patient not found</div>";
    exit();
}

$patient = mysqli_fetch_assoc($patientResult);


$appointmentsQuery = "SELECT a.*, d.DoctorName 
                     FROM Appointments a 
                     LEFT JOIN Doctors d ON a.DoctorID = d.DoctorID 
                     WHERE a.PatientID = '$patientId' 
                     ORDER BY a.AppointmentDate DESC, a.AppointmentTime DESC";
$appointmentsResult = mysqli_query($conn, $appointmentsQuery);

if (!$appointmentsResult) {
    logError("Error fetching patient appointments: " . mysqli_error($conn));
}


$registerDate = isset($patient['RegisterDate']) ? date('F j, Y', strtotime($patient['RegisterDate'])) : 'Not available';
$status = $patient['Status'] ?? 'Active';
$statusClass = 'outpatient'; 

if ($status == 'Admitted') {
    $statusClass = 'admitted';
} elseif ($status == 'Emergency') {
    $statusClass = 'emergency';
} elseif ($status == 'Scheduled') {
    $statusClass = 'scheduled';
}


$initials = '';
$nameParts = explode(' ', $patient['PatientName']);
foreach ($nameParts as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
$initials = substr($initials, 0, 2);


?>
<style>
    .patient-info-card {
        padding: 1.5rem;
    }

    .patient-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 600;
        color: #666;
        margin: 0 auto 1rem;
    }

    .patient-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .patient-name {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0.5rem 0;
    }

    .info-row {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .info-label {
        flex: 0 0 30%;
        font-weight: 600;
        color: #666;
    }

    .info-value {
        flex: 0 0 70%;
    }

    .status-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
    }

    .appointments-section {
        margin-top: 2rem;
    }

    .appointment-item {
        border-left: 3px solid #5b56e8;
        padding: 0.75rem 1rem;
        margin-bottom: 0.75rem;
        background-color: #f9f9f9;
        border-radius: 0 4px 4px 0;
    }

    .appointment-date {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .appointment-doctor {
        font-size: 0.9rem;
        color: #666;
    }

    .appointment-purpose {
        font-size: 0.85rem;
        margin-top: 0.25rem;
        color: #333;
    }

    .no-appointments {
        text-align: center;
        padding: 1rem;
        background-color: #f9f9f9;
        border-radius: 4px;
        color: #666;
    }
</style>

<div class="patient-info-card">
    <div class="patient-header">
        <div class="patient-avatar"><?php echo $initials; ?></div>
        <h4 class="patient-name"><?php echo htmlspecialchars($patient['PatientName']); ?></h4>
        <span class="status-badge status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
        <p class="text-muted mt-2">Patient since <?php echo $registerDate; ?></p>
    </div>

    <h5>Personal Information</h5>
    <div class="info-row">
        <div class="info-label">Patient ID</div>
        <div class="info-value"><?php echo $patient['PatientID']; ?></div>
    </div>
    <div class="info-row">
        <div class="info-label">Email</div>
        <div class="info-value"><?php echo htmlspecialchars($patient['Email'] ?? 'Not provided'); ?></div>
    </div>
    <div class="info-row">
        <div class="info-label">Phone</div>
        <div class="info-value"><?php echo htmlspecialchars($patient['Phone'] ?? 'Not provided'); ?></div>
    </div>
    <div class="info-row">
        <div class="info-label">Age</div>
        <div class="info-value"><?php echo $patient['Age'] ?? 'Not provided'; ?></div>
    </div>
    <div class="info-row">
        <div class="info-label">Gender</div>
        <div class="info-value"><?php echo htmlspecialchars($patient['Gender'] ?? 'Not provided'); ?></div>
    </div>
    <div class="info-row">
        <div class="info-label">Blood Type</div>
        <div class="info-value"><?php echo htmlspecialchars($patient['BloodType'] ?? 'Not provided'); ?></div>
    </div>
    <div class="info-row">
        <div class="info-label">Address</div>
        <div class="info-value"><?php echo htmlspecialchars($patient['Address'] ?? 'Not provided'); ?></div>
    </div>

    <h5 class="mt-4">Medical Information</h5>
    <div class="info-row">
        <div class="info-label">Medical History</div>
        <div class="info-value">
            <?php echo htmlspecialchars($patient['MedicalHistory'] ?? 'No medical history recorded'); ?></div>
    </div>

    <h5 class="mt-4">Emergency Contact</h5>
    <div class="info-row">
        <div class="info-label">Name</div>
        <div class="info-value"><?php echo htmlspecialchars($patient['EmergencyContact'] ?? 'Not provided'); ?></div>
    </div>
    <div class="info-row">
        <div class="info-label">Phone</div>
        <div class="info-value"><?php echo htmlspecialchars($patient['EmergencyPhone'] ?? 'Not provided'); ?></div>
    </div>

    <div class="appointments-section">
        <h5>Recent Appointments</h5>
        <?php if ($appointmentsResult && mysqli_num_rows($appointmentsResult) > 0): ?>
            <?php $count = 0; ?>
            <?php while ($appointment = mysqli_fetch_assoc($appointmentsResult) and $count < 5): ?>
                <?php $count++; ?>
                <div class="appointment-item">
                    <div class="appointment-date">
                        <?php echo date('F j, Y', strtotime($appointment['AppointmentDate'])); ?> at
                        <?php echo date('h:i A', strtotime($appointment['AppointmentTime'])); ?>
                    </div>
                    <div class="appointment-doctor">
                        Doctor: <?php echo htmlspecialchars($appointment['DoctorName'] ?? 'Not assigned'); ?>
                    </div>
                    <div class="appointment-purpose">
                        Purpose: <?php echo htmlspecialchars($appointment['AppointmentPurpose'] ?? 'Not specified'); ?>
                    </div>
                </div>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($appointmentsResult) > 5): ?>
                <div class="text-center mt-2">
                    <small class="text-muted">Showing 5 of <?php echo mysqli_num_rows($appointmentsResult); ?>
                        appointments</small>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-appointments">
                No appointments found for this patient
            </div>
        <?php endif; ?>
    </div>
</div>