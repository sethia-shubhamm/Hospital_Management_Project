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
    $bloodType = $patient['BloodType'] ?? 'Not recorded';
    $patientAge = $patient['PatientAge'] ?? 'N/A';
    $patientGender = $patient['PatientGender'] ?? 'Not specified';
} else {
    $patientName = "Patient";
    $patientID = $login_id;
    $bloodType = 'Not recorded';
    $patientAge = 'N/A';
    $patientGender = 'Not specified';
}


$formatted_patient_id = 'PAT-' . date('Y') . '-' . str_pad($patientID, 3, '0', STR_PAD_LEFT);


$records_query = "SELECT mr.*, d.DoctorName, d.Specialty FROM MedicalRecords mr 
                 LEFT JOIN Doctors d ON mr.DoctorID = d.DoctorID 
                 WHERE mr.PatientID = '$patientID' 
                 ORDER BY mr.RecordDate DESC";
$records_result = mysqli_query($conn, $records_query);


$visits_query = "SELECT a.*, d.DoctorName, d.Specialty FROM Appointments a 
                LEFT JOIN Doctors d ON a.DoctorID = d.DoctorID 
                WHERE a.PatientID = '$patientID' 
                ORDER BY a.AppointmentDate DESC, a.AppointmentTime DESC 
                LIMIT 5";
$visits_result = mysqli_query($conn, $visits_query);


$has_lab_reports = false;
$has_vitals = false;


$lab_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'LabReports'");
if (mysqli_num_rows($lab_table_check) > 0) {
    $has_lab_reports = true;
    $lab_query = "SELECT * FROM LabReports WHERE PatientID = '$patientID' ORDER BY ReportDate DESC LIMIT 5";
    $lab_result = mysqli_query($conn, $lab_query);
}


$vitals_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'VitalSigns'");
if (mysqli_num_rows($vitals_table_check) > 0) {
    $has_vitals = true;
    $vitals_query = "SELECT * FROM VitalSigns WHERE PatientID = '$patientID' ORDER BY RecordDate DESC LIMIT 5";
    $vitals_result = mysqli_query($conn, $vitals_query);
}
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
                        <img src="icons/dashboard.png" alt="">
                        <a href="../dashboard/index.php" style="text-decoration: none;">
                            <h6>Dashboard</h6>
                        </a>
                    </div>
                    <div>
                        <img src="icons/profile.png" alt="">
                        <a href="../profile/index.php" style="text-decoration: none;">
                            <h6>Profile</h6>
                        </a>
                    </div>
                    <div>
                        <img src="icons/appointment.png" alt="">
                        <a href="../appointment/index.php" style="text-decoration: none;">
                            <h6>Appointment</h6>
                        </a>
                    </div>
                    <div class="active">
                        <img src="icons/records.png" alt="">
                        <a href="index.php" style="text-decoration: none;">
                            <h6>Records</h6>
                        </a>
                    </div>
                    <div>
                        <img src="icons/invoice.png" alt="">
                        <a href="../payments/index.php" style="text-decoration: none;">
                            <h6>Payments</h6>
                        </a>
                    </div>
                    <div style="height: 52.25px;">
                        <img src="icons/insurance.png" alt="" style="height: 32.25px; margin-left: 5px;">
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
                    <h1>Medical Records</h1>
                    <p>View your complete medical history</p>
                </div>

                <div class="records-container">
                    <div class="records-header">
                        <div class="patient-info">
                            <div class="info-item">
                                <span>Patient ID:</span>
                                <p><?php echo htmlspecialchars($formatted_patient_id); ?></p>
                            </div>
                            <div class="info-item">
                                <span>Blood Group:</span>
                                <p><?php echo htmlspecialchars($bloodType); ?></p>
                            </div>
                            <div class="info-item">
                                <span>Age:</span>
                                <p><?php echo htmlspecialchars($patientAge); ?> Years</p>
                            </div>
                            <div class="info-item">
                                <span>Gender:</span>
                                <p><?php echo htmlspecialchars($patientGender); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="records-grid">
                        <!-- Medical Records Card - Now Full Width -->
                        <div class="record-card full-width-card">
                            <div class="record-header">
                                <h3>Medical Records</h3>
                            </div>
                            <div class="medical-records-list">
                                <?php if ($records_result && mysqli_num_rows($records_result) > 0): ?>
                                    <table class="records-table">
                                        <thead>
                                            <tr>
                                                <th>Condition</th>
                                                <th>Date</th>
                                                <th>Doctor</th>
                                                <th>Treatment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($record = mysqli_fetch_assoc($records_result)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['MedicalCondition'] ?? 'Not specified'); ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($record['RecordDate'])); ?></td>
                                                    <td><?php echo htmlspecialchars($record['DoctorName'] ?? 'Unknown'); ?>
                                                        <?php if (!empty($record['Specialty'])): ?>
                                                            <span
                                                                class="specialty">(<?php echo htmlspecialchars($record['Specialty']); ?>)</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($record['TreatmentInfo'] ?? 'None'); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="no-data">
                                        <p>No medical records available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .no-data {
            text-align: center;
            padding: 20px 10px;
            color: #777;
            font-style: italic;
        }

        .record-card {
            min-height: 200px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .record-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }

        .record-header h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }

       
        .full-width-card {
            grid-column: 1 / -1;
            width: 100%;
        }

        .medical-records-list {
            max-height: 600px;
            overflow-y: auto;
            padding: 15px;
        }

       
        .record-cards-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .medical-record-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 16px;
            border: 1px solid #f0f0f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .medical-record-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .record-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .record-card-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .record-date {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .record-doctor {
            margin-bottom: 12px;
        }

        .record-doctor strong {
            font-size: 14px;
            color: #7260ff;
            font-weight: 500;
        }

        .doctor-specialty {
            font-size: 13px;
            color: #666;
            font-style: italic;
            margin-left: 5px;
        }

        .record-divider {
            height: 1px;
            background: repeating-linear-gradient(to right, #ddd 0, #ddd 5px, transparent 5px, transparent 10px);
            margin: 10px 0;
        }

        .record-treatment {
            padding-top: 8px;
        }

        .treatment-label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }

        .treatment-info {
            font-size: 14px;
            color: #333;
            line-height: 1.4;
        }

       
        .content-area {
            margin-top: -37px;
        }

        .welcome-section {
            margin-top: 10px;
        }
    </style>
</body>

</html>