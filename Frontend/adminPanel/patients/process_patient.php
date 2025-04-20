<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    $_SESSION['login_error'] = "Please log in as an administrator to access this page";
    header("Location: ../../adminLogin/index.php");
    exit();
}


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


require_once '../../../db_connect.php';


$response = [
    'success' => false,
    'message' => ''
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $patientName = isset($_POST['patientName']) ? mysqli_real_escape_string($conn, $_POST['patientName']) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : NULL;
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : NULL;
    $age = isset($_POST['age']) && !empty($_POST['age']) ? (int) $_POST['age'] : NULL;
    $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : NULL;
    $bloodType = isset($_POST['bloodType']) ? mysqli_real_escape_string($conn, $_POST['bloodType']) : NULL;
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : NULL;
    $medicalHistory = isset($_POST['medicalHistory']) ? mysqli_real_escape_string($conn, $_POST['medicalHistory']) : NULL;
    $emergencyContact = isset($_POST['emergencyContact']) ? mysqli_real_escape_string($conn, $_POST['emergencyContact']) : NULL;
    $emergencyPhone = isset($_POST['emergencyPhone']) ? mysqli_real_escape_string($conn, $_POST['emergencyPhone']) : NULL;
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'Active';
    $registerDate = isset($_POST['registerDate']) && !empty($_POST['registerDate']) ? $_POST['registerDate'] : date('Y-m-d');

    
    if (empty($patientName)) {
        $response['message'] = "Patient name is required.";
    } else {
        
        if (!empty($email)) {
            $checkDuplicateQuery = "SELECT * FROM Patients WHERE PatientName = '$patientName' AND Email = '$email'";
            $duplicateResult = mysqli_query($conn, $checkDuplicateQuery);

            if (!$duplicateResult) {
                logError("Error checking duplicate patient: " . mysqli_error($conn));
                $response['message'] = "Database error occurred. Please try again.";
            } elseif (mysqli_num_rows($duplicateResult) > 0) {
                $response['message'] = "A patient with this name and email already exists.";
            }
        }

        
        if (empty($response['message'])) {
            
            $insertPatientQuery = "INSERT INTO Patients (
                PatientName, Email, Phone, Age, Gender, BloodType, Address, 
                MedicalHistory, EmergencyContact, EmergencyPhone, Status, RegisterDate
            ) VALUES (
                '$patientName',
                " . ($email ? "'$email'" : "NULL") . ",
                " . ($phone ? "'$phone'" : "NULL") . ",
                " . ($age ? "$age" : "NULL") . ",
                " . ($gender ? "'$gender'" : "NULL") . ",
                " . ($bloodType ? "'$bloodType'" : "NULL") . ",
                " . ($address ? "'$address'" : "NULL") . ",
                " . ($medicalHistory ? "'$medicalHistory'" : "NULL") . ",
                " . ($emergencyContact ? "'$emergencyContact'" : "NULL") . ",
                " . ($emergencyPhone ? "'$emergencyPhone'" : "NULL") . ",
                '$status',
                '$registerDate'
            )";

            if (mysqli_query($conn, $insertPatientQuery)) {
                $response['success'] = true;
                $response['message'] = "Patient added successfully!";
            } else {
                logError("Failed to add patient: " . mysqli_error($conn));
                $response['message'] = "Failed to add patient. Please try again.";
            }
        }
    }
}


if ($response['success']) {
    $_SESSION['success_message'] = $response['message'];
} else {
    $_SESSION['error_message'] = $response['message'];
}


header("Location: index.php");
exit();
?>