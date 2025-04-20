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
    $patientAge = "";
    $patientGender = "";
    $bloodType = "";
    $contactInfo = "";
} else if (mysqli_num_rows($patient_result) > 0) {
    $patient = mysqli_fetch_assoc($patient_result);
    $patientID = $patient['PatientID'];
    $patientName = $patient['PatientName'] ?? "Patient User";
    $patientAge = $patient['PatientAge'] ?? "";
    $patientGender = $patient['PatientGender'] ?? "";
    $bloodType = $patient['BloodType'] ?? "";
    $contactInfo = $patient['ContactInfo'] ?? "";
} else {
    
    $user_query = "SELECT * FROM LoginCredentials WHERE LoginID = '$login_id'";
    $user_result = mysqli_query($conn, $user_query);

    if (!$user_result) {
        logError("User query failed: " . mysqli_error($conn));
        $patientID = $login_id;
        $patientName = "Patient User";
        $patientAge = "";
        $patientGender = "";
        $bloodType = "";
        $contactInfo = "";
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
            $patientAge = "";
            $patientGender = "";
            $bloodType = "";
            $contactInfo = "";
        } else {
            $patient = mysqli_fetch_assoc($patient_result);
            $patientID = $patient['PatientID'];
            $patientName = $patient['PatientName'] ?? "Patient User";
            $patientAge = $patient['PatientAge'] ?? "";
            $patientGender = $patient['PatientGender'] ?? "";
            $bloodType = $patient['BloodType'] ?? "";
            $contactInfo = $patient['ContactInfo'] ?? "";
        }
    }
}


$patientFields = array(
    'FirstName' => '',
    'LastName' => '',
    'DateOfBirth' => '',
    'Address' => '',
    'City' => '',
    'State' => '',
    'ZipCode' => '',
    'PhoneNumber' => $contactInfo,
    'EmergencyContact' => '',
    'EmergencyPhone' => '',
    'MedicalHistory' => '',
    'Allergies' => '',
    'Insurance' => ''
);


$detailsTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'PatientDetails'");
if (mysqli_num_rows($detailsTableCheck) > 0) {
    $details_query = "SELECT * FROM PatientDetails WHERE PatientID = '$patientID'";
    $details_result = mysqli_query($conn, $details_query);

    if (!$details_result) {
        logError("Patient details query failed: " . mysqli_error($conn));
    } else if (mysqli_num_rows($details_result) > 0) {
        $details = mysqli_fetch_assoc($details_result);

        
        foreach ($details as $key => $value) {
            if (array_key_exists($key, $patientFields) && $value) {
                $patientFields[$key] = $value;
            }
        }
    }
}


if (empty($patientFields['FirstName']) && strpos($patientName, ' ') !== false) {
    $nameParts = explode(' ', $patientName, 2);
    $patientFields['FirstName'] = $nameParts[0];
    $patientFields['LastName'] = $nameParts[1];
} else if (empty($patientFields['FirstName'])) {
    $patientFields['FirstName'] = $patientName;
}


$fullAddress = '';
if (!empty($patientFields['Address'])) {
    $fullAddress .= $patientFields['Address'];

    if (!empty($patientFields['City'])) {
        $fullAddress .= ', ' . $patientFields['City'];
    }

    if (!empty($patientFields['State'])) {
        $fullAddress .= ', ' . $patientFields['State'];
    }

    if (!empty($patientFields['ZipCode'])) {
        $fullAddress .= ' ' . $patientFields['ZipCode'];
    }
} else {
    $fullAddress = '';
}


$dateOfBirth = !empty($patientFields['DateOfBirth']) ? $patientFields['DateOfBirth'] : '';
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
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #6f5fff;
        }

        .desktop {
            display: flex;
            min-height: 100vh;
        }

       
        .sidebar {
            width: 230px;
            background-color: white;
            height: 100vh;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            left: 0;
            top: 0;
        }

        .logo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }

        .logo-section img {
            width: 40px;
            height: 40px;
            margin-bottom: 5px;
        }

        .logo-section h6 {
            margin: 0;
            font-size: 14px;
            color: #333;
            text-align: center;
        }

        .nav-link-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: background-color 0.2s;
        }

        .nav-link-item.active {
            background-color: #7260ff;
            color: white;
        }

        .nav-link-item img,
        .nav-link-item svg {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .logout-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            margin: 5px 10px;
            color: #ff3b30;
            text-decoration: none;
        }

        .logout-link img,
        .logout-link svg {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            filter: invert(37%) sepia(74%) saturate(7471%) hue-rotate(353deg) brightness(95%) contrast(127%);
        }

       
        .content-wrapper {
            margin-left: 230px;
            width: calc(100% - 230px);
            padding: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .medical-info {
            grid-column: span 2;
        }

        .profile-field {
            margin-bottom: 12px;
            display: flex;
        }

        .field-label {
            font-weight: 600;
            min-width: 140px;
            color: #444;
        }

        .field-value {
            color: #666;
        }

        .welcome-section {
            margin-bottom: 20px;
        }

        .welcome-section h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .welcome-section p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
        }

        .info-card h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e3e3e3;
            color: #7260ff;
        }
    </style>
</head>

<body>
    <div class="desktop">
        <!-- Sidebar -->
        <div class="sidebar">
            <div>
                <div class="logo-section">
                    <img src="icons/logo.png" alt="Logo"> 
                    <h6>Seattle Grace Hospital</h6>
                </div>
                

                <div class="menu-items">
                    <div>
                        <img src="../dashboard/icons/dashboard.png" alt="">
                        <a href="../dashboard/index.php" style="text-decoration: none;">
                            <h6>Dashboard</h6>
                        </a>
                    </div>
                    <div class="active">
                        <img src="../dashboard/icons/profile.png" alt="">
                        <a href="index.php" style="text-decoration: none;">
                            <h6>Profile</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/appointment.png" alt="">
                        <a href="../appointment/index.php" style="text-decoration: none;">
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
                        <img src="icons/insurance.png" alt="" style="height: 32.25px;">
                        <a href="../insurance/index.php" style="text-decoration: none;">
                            <h6>Insurance</h6>
                        </a>
                    </div>
                </div>
            </div>

            <a href="../../logout.php" class="logout-link">
                <svg xmlns="http:
                    <path
                        d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
                </svg>
                Logout
            </a>
        </div>

        <!-- Content Area -->
        <div class="content-wrapper">
            <div class="welcome-section">
                <h1 id="welcomeMessage">Profile Information</h1>
                <p id="currentDate">Patient ID: <?php echo htmlspecialchars($patientID); ?></p>
            </div>

            <div class="info-grid">
                <div class="info-card personal-info">
                    <h3>Personal Information</h3>
                    <div class="profile-content">
                        <div class="profile-field">
                            <span class="field-label">Name:</span>
                            <span class="field-value"><?php echo htmlspecialchars($patientName); ?></span>
                        </div>
                        
                        <div class="profile-field">
                            <span class="field-label">Gender:</span>
                            <span
                                class="field-value"><?php echo htmlspecialchars($patientGender) ?: 'Not provided'; ?></span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Blood Type:</span>
                            <span
                                class="field-value"><?php echo htmlspecialchars($bloodType) ?: 'Not provided'; ?></span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Phone:</span>
                            <span
                                class="field-value"><?php echo htmlspecialchars($contactInfo) ?: 'Not provided'; ?></span>
                        </div>
                        
                    </div>
                </div>

                <div class="info-card account-info">
                    <h3>Account Settings</h3>
                    <div class="profile-content">
                        <div class="profile-field">
                            <span class="field-label">Email:</span>
                            <span class="field-value"><?php echo htmlspecialchars($email); ?></span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">User Type:</span>
                            <span class="field-value">Patient</span>
                        </div>
                        <div class="profile-field">
                            <span class="field-label">Account ID:</span>
                            <span class="field-value"><?php echo htmlspecialchars($login_id); ?></span>
                        </div>
                    </div>
                </div>

                
            </div>
        </div>
    </div>
</body>

</html>