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


$patientsTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Patients'");
if (mysqli_num_rows($patientsTableCheck) == 0) {
    logError("Patients table does not exist, creating it");
    
    $createPatientsTable = "CREATE TABLE Patients (
        PatientID INT PRIMARY KEY,
        PatientName VARCHAR(100) NOT NULL,
        PatientAge INT NULL,
        PatientGender VARCHAR(10) NULL,
        BloodType VARCHAR(5) NULL,
        ContactInfo VARCHAR(100) NULL
    )";
    if (!mysqli_query($conn, $createPatientsTable)) {
        logError("Failed to create Patients table: " . mysqli_error($conn));
    }
}


$patient_check_query = "SELECT * FROM Patients WHERE PatientID = '$login_id'";
$patient_result = mysqli_query($conn, $patient_check_query);

if (!$patient_result) {
    logError("Patient check query failed: " . mysqli_error($conn));
    $patientID = $login_id;
    $patientName = "Patient User";
} else if (mysqli_num_rows($patient_result) > 0) {
    $patient = mysqli_fetch_assoc($patient_result);
    $patientID = $patient['PatientID'];
    $patientName = $patient['PatientName'];
} else {
    
    $user_query = "SELECT * FROM LoginCredentials WHERE LoginID = '$login_id'";
    $user_result = mysqli_query($conn, $user_query);

    if (!$user_result) {
        logError("User query failed: " . mysqli_error($conn));
        $patientID = $login_id;
        $patientName = "Patient User";
    } else {
        $user = mysqli_fetch_assoc($user_result);

        
        $create_patient_query = "INSERT INTO Patients (PatientID, PatientName, PatientAge, PatientGender, BloodType, ContactInfo) 
                            VALUES ('$login_id', 'Patient User', NULL, NULL, NULL, NULL)";
        $create_result = mysqli_query($conn, $create_patient_query);

        if (!$create_result) {
            logError("Patient creation failed: " . mysqli_error($conn));
        }

        
        $patient_result = mysqli_query($conn, $patient_check_query);
        if (!$patient_result) {
            logError("Patient re-fetch failed: " . mysqli_error($conn));
            $patientID = $login_id;
            $patientName = "Patient User";
        } else {
            $patient = mysqli_fetch_assoc($patient_result);
            $patientID = $patient['PatientID'];
            $patientName = $patient['PatientName'];
        }
    }
}


$appointmentsTableCheck = "SHOW TABLES LIKE 'Appointments'";
$appointmentsTableExists = mysqli_query($conn, $appointmentsTableCheck);

$patientIdCol = 'PatientID';
$doctorIdCol = 'DoctorID';
$apptResult = false;

if (mysqli_num_rows($appointmentsTableExists) > 0) {
    
    $columnCheckQuery = "SHOW COLUMNS FROM Appointments";
    $columnResult = mysqli_query($conn, $columnCheckQuery);

    if (!$columnResult) {
        logError("Column check query failed: " . mysqli_error($conn));
    } else {
        
        while ($column = mysqli_fetch_assoc($columnResult)) {
            $colName = $column['Field'];
            if (
                strtolower($colName) === 'patientid' ||
                strtolower($colName) === 'patient_id'
            ) {
                $patientIdCol = $colName;
            }
            if (
                strtolower($colName) === 'doctorid' ||
                strtolower($colName) === 'doctor_id'
            ) {
                $doctorIdCol = $colName;
            }
        }

        
        mysqli_data_seek($columnResult, 0);

        
        $apptQuery = "SELECT a.*, d.DoctorName FROM Appointments a 
                    JOIN Doctors d ON a.$doctorIdCol = d.DoctorID 
                    WHERE a.$patientIdCol = '$patientID' AND a.AppointmentDate >= CURDATE() 
                    ORDER BY a.AppointmentDate, a.AppointmentTime LIMIT 3";
        $apptResult = mysqli_query($conn, $apptQuery);

        if (!$apptResult) {
            logError("Appointment query failed: " . mysqli_error($conn));
            $apptResult = false;
        }
    }
} else {
    logError("Appointments table does not exist");
    $apptResult = false;
}


$recordsTableCheck = "SHOW TABLES LIKE 'MedicalRecords'";
$recordsTableExists = mysqli_query($conn, $recordsTableCheck);
$recordResult = false;

if (mysqli_num_rows($recordsTableExists) > 0) {
    
    $columnsQuery = "SHOW COLUMNS FROM MedicalRecords";
    $columnsResult = mysqli_query($conn, $columnsQuery);
    $recordPatientIdCol = 'PatientID'; 
    $recordDoctorIdCol = 'DoctorID'; 

    if ($columnsResult) {
        $columns = array();
        while ($column = mysqli_fetch_assoc($columnsResult)) {
            $colName = $column['Field'];
            
            if (strtolower($colName) === 'patientid' || strtolower($colName) === 'patient_id') {
                $recordPatientIdCol = $colName;
            }
            
            if (strtolower($colName) === 'doctorid' || strtolower($colName) === 'doctor_id') {
                $recordDoctorIdCol = $colName;
            }
        }
    } else {
        logError("MedicalRecords column check failed: " . mysqli_error($conn));
    }

    
    $recordQuery = "SELECT mr.*, d.DoctorName FROM MedicalRecords mr 
                  JOIN Doctors d ON mr.$recordDoctorIdCol = d.DoctorID 
                  WHERE mr.$recordPatientIdCol = '$patientID' 
                  ORDER BY mr.RecordDate DESC LIMIT 3";
    $recordResult = mysqli_query($conn, $recordQuery);

    if (!$recordResult) {
        logError("Medical records query failed: " . mysqli_error($conn));
        $recordResult = false;
    }
} else {
    logError("MedicalRecords table does not exist");
}


$paymentsTableCheck = "SHOW TABLES LIKE 'Payments'";
$paymentsTableExists = mysqli_query($conn, $paymentsTableCheck);
$lastPayment = null;

if (mysqli_num_rows($paymentsTableExists) > 0) {
    
    $columnsQuery = "SHOW COLUMNS FROM Payments";
    $columnsResult = mysqli_query($conn, $columnsQuery);
    $paymentPatientIdCol = 'PatientID'; 
    $billIdCol = 'BillID';

    if ($columnsResult) {
        $hasPatientId = false;
        $columns = array();
        while ($column = mysqli_fetch_assoc($columnsResult)) {
            $colName = $column['Field'];
            $columns[] = $colName;
            
            if (strtolower($colName) === 'patientid' || strtolower($colName) === 'patient_id') {
                $paymentPatientIdCol = $colName;
                $hasPatientId = true;
            }
            
            if (strtolower($colName) === 'billid' || strtolower($colName) === 'bill_id') {
                $billIdCol = $colName;
            }
        }

        
        if (!$hasPatientId) {
            
            $paymentQuery = "SELECT p.*, b.PatientID FROM Payments p 
                          JOIN Bills b ON p.$billIdCol = b.BillID 
                          WHERE b.PatientID = '$patientID' 
                          ORDER BY p.PaymentDate DESC LIMIT 1";
        } else {
            
            $paymentQuery = "SELECT * FROM Payments 
                          WHERE $paymentPatientIdCol = '$patientID' 
                          ORDER BY PaymentDate DESC LIMIT 1";
        }
    } else {
        
        logError("Payment columns check failed: " . mysqli_error($conn));
        $paymentQuery = "SELECT p.*, b.PatientID FROM Payments p 
                       JOIN Bills b ON p.BillID = b.BillID 
                       WHERE b.PatientID = '$patientID' 
                       ORDER BY p.PaymentDate DESC LIMIT 1";
    }

    $paymentResult = mysqli_query($conn, $paymentQuery);

    if (!$paymentResult) {
        logError("Payment query failed: " . mysqli_error($conn));
    } else {
        $lastPayment = mysqli_fetch_assoc($paymentResult);
    }
} else {
    logError("Payments table does not exist");
}


$tableCheckQuery = "SHOW TABLES LIKE 'Bills'";
$tableExists = mysqli_query($conn, $tableCheckQuery);
$billResult = false;


$billsQuery = "SELECT * FROM Bills WHERE PatientID = '{$patientID}' AND BillAmount > 0 
ORDER BY BillDate DESC";

$billResult = mysqli_query($conn, $billsQuery);

if (!$billResult) {
    logError("Bill query failed: " . mysqli_error($conn));
    $billResult = false;
}


function formatDate($dateString)
{
    if (!$dateString)
        return '';
    return date('l, d F Y', strtotime($dateString));
}


function formatCurrency($amount)
{
    if (!$amount)
        return '$0.00';
    return '$' . number_format($amount, 2);
}


$today = formatDate(date('Y-m-d'));
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, width=device-width">

    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>" />
	  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300&display=swap" />
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
                    <div class="active">
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
                    <div>
                        <img src="icons/records.png" alt="">
                        <a href="../records/index.php" style="text-decoration: none;">
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
                        <img src="icons/insurance.png" alt="" style="height: 32.25px;">
                        <a href="../insurance/index.php" style="text-decoration: none;">
                            <h6>Insurance</h6>
                        </a>
                    </div>
                </div>
                <div class="logout">
                    <img src="icons/Logout.png" alt="">
                    <a href="../../logout.php" style="text-decoration: none; color: inherit;">
                        <h6>Logout</h6>
                    </a>
                </div>
            </div>

            <div class="content-area">
                <div class="welcome-section">
                    <h1 id="welcomeMessage">Welcome back, <span><?php echo htmlspecialchars($patientName); ?></span>
                    </h1>
                    <p id="currentDate"><?php echo $today; ?></p>
                </div>

                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <img src="icons/appointment.png" alt="">
                        </div>
                        <div class="stat-info">
                            <h3>Next Appointment</h3>
                            <p id="nextAppointment">
                                <?php
                                if ($apptResult && mysqli_num_rows($apptResult) > 0) {
                                    $nextAppt = mysqli_fetch_assoc($apptResult);
                                    echo date('F d, Y', strtotime($nextAppt['AppointmentDate'])) . ', ' .
                                        date('h:i A', strtotime($nextAppt['AppointmentTime']));
                                    
                                    mysqli_data_seek($apptResult, 0);
                                } else {
                                    echo "No upcoming appointments";
                                }
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <img src="icons/invoice.png" alt="">
                        </div>
                        <div class="stat-info">
                            <h3>Last Payment</h3>
                            <p id="lastPayment">
                                <?php
                                if ($lastPayment) {
                                    $paymentAmount = isset($lastPayment['Amount']) ? $lastPayment['Amount'] :
                                        (isset($lastPayment['PaymentAmount']) ? $lastPayment['PaymentAmount'] :
                                            (isset($lastPayment['AmountPaid']) ? $lastPayment['AmountPaid'] : 0));

                                    echo formatCurrency($paymentAmount) .
                                        (isset($lastPayment['PaymentDate']) ?
                                            '<br><small>' . date('M d, Y', strtotime($lastPayment['PaymentDate'])) . '</small>' : '');
                                } else {
                                    echo "No payment records";
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-card appointments">
                        <h3>Upcoming Appointments</h3>
                        <div id="appointmentsList">
                            <?php if ($apptResult && mysqli_num_rows($apptResult) > 0): ?>
                                <?php while ($appt = mysqli_fetch_assoc($apptResult)): ?>
                                    <div class="appointment-item">
                                        <img src="icons/doctor.png" alt="">
                                        <div>
                                            <h4>Dr. <?php echo htmlspecialchars($appt['DoctorName']); ?></h4>
                                            <p><?php echo $appt['AppointmentPurpose'] ?? 'Regular Checkup'; ?></p>
                                            <span>
                                                <?php
                                                echo date('F d, Y', strtotime($appt['AppointmentDate'])) . ', ' .
                                                    date('h:i A', strtotime($appt['AppointmentTime']));
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-data">No upcoming appointments</div>
                                <a href="../appointment/index.php" class="btn btn-primary mt-3">Schedule Appointment</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-card records">
                        <h3>Recent Medical Records</h3>
                        <div id="recordsList">
                            <?php if ($recordResult && mysqli_num_rows($recordResult) > 0): ?>
                                <?php while ($record = mysqli_fetch_assoc($recordResult)): ?>
                                    <div class="record-item">
                                        <h4><?php echo htmlspecialchars($record['MedicalCondition']); ?></h4>
                                        <p>Dr. <?php echo htmlspecialchars($record['DoctorName']); ?></p>
                                        <p><?php echo date('F d, Y', strtotime($record['RecordDate'])); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-data">No medical records available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>

<style>
    .bills-table {
        width: 100%;
        overflow-x: auto;
    }

    .bills-table table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .bills-table th {
        background-color: #f5f5f5;
        padding: 8px 12px;
        text-align: left;
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #ddd;
    }

    .bills-table td {
        padding: 8px 12px;
        border-bottom: 1px solid #eee;
    }

    .bills-table tr:hover {
        background-color: #f9f9f9;
    }

    .text-danger {
        color: #d63031;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
        padding-top: 10px;
        border-top: 2px solid #eee;
        font-weight: 600;
    }

    .total-amount {
        color: #2ecc71;
    }

    .desktop {
        width: 100%;
        height: 100vh;
        background: linear-gradient(179.8deg, #7260ff, #3e398f);
        display: flex;
        flex-direction: column;
        align-items: center;
        overflow: hidden;
    }

    .mainContainer {
        width: 100%;
        height: calc(100vh - 80px);
        display: flex;
        position: relative;
        overflow: hidden;
    }

    .choiceSection {
        margin-left: 35px;
        margin-top: 35px;
        width: 280px;
        min-height: 600px;
        position: fixed;
        left: 0;
        top: 80px;
        box-shadow: 0px 3px 4px 5px rgba(0, 0, 0, 0.25);
        border-radius: 10px;
        background-color: #f8f8f8;
        padding: 30px;
        display: flex;
        flex-direction: column;
        gap: 30px;
        height: 20px;
    }

    .choiceSection img {
        width: 18px;
        height: 18px;
        object-fit: contain;
        width: 47.5px;
        height: 37.6px;
    }

    .menu-items {
        display: flex;
        flex-direction: column;
        gap: 20px;
        
    }

    .menu-items > div {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        height: 53px;
        width: 220px;
    }

    .menu-items > div:hover {
        background-color: rgba(114, 96, 255, 0.1);
    }

    .menu-items > div.active {
        background-color: #7260ff;
    }

    .menu-items > div.active h6 {
        color: white;
    }

    .menu-items > div img {
        width: 47.5px;
        height: 37.6px;
        object-fit: contain

    }

    .menu-items h6 {
        margin: 0;
        font-size: 14px;
        color: #333;
        font-weight: 500;
        height: 16.8px;
        width: 50.26px;
        font-weight: bold;
    }

    .logout {
        margin-top: auto;
        margin-left: 12px;
        padding: 10px 15px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .logout h6 {
        color: #ff6060;
        font-size: 12px;
        margin: 0;
        font-weight: 500;
    }

    .logout img {
        width: 20%;
        height: auto;
        object-fit: contain;
    }

    .logout:hover {
        background-color: rgba(255, 96, 96, 0.1);
    }

    .content-area {
        margin-left: 350px;
        margin-top: -40px;
        padding: 35px;
        height: calc(100vh - 80px);
        overflow: hidden;
    }

    @media screen and (max-width: 768px) {
        .choiceSection {
            width: 95%;
            padding: 20px;
            position: relative;
            margin: 0;
            min-height: auto;
            top: 0;
        }

        .content-area {
            margin-left: 0;
            margin-top: 0;
        }
    }
</style>