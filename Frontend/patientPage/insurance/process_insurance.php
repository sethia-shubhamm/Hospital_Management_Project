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


$patient_check_query = "SELECT * FROM Patients WHERE PatientID = '$login_id'";
$patient_result = mysqli_query($conn, $patient_check_query);

if (!$patient_result || mysqli_num_rows($patient_result) == 0) {
    logError("Patient not found for login ID: $login_id");
    $_SESSION['insurance_error'] = "Patient record not found";
    header("Location: index.php");
    exit();
}

$patient = mysqli_fetch_assoc($patient_result);
$patientID = $patient['PatientID'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $providerName = mysqli_real_escape_string($conn, $_POST['providerName'] ?? '');
    $policyNumber = mysqli_real_escape_string($conn, $_POST['policyNumber'] ?? '');
    
    if (empty($providerName) || empty($policyNumber)) {
        $_SESSION['insurance_error'] = "Provider name and policy number are required";
        header("Location: index.php");
        exit();
    }
    
    if ($action === 'add') {
        
        $check_query = "SELECT * FROM Insurance WHERE PatientID = '$patientID'";
        $check_result = mysqli_query($conn, $check_query);
        
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            
            $update_query = "UPDATE Insurance SET ProviderName = '$providerName', PolicyNumber = '$policyNumber' 
                            WHERE PatientID = '$patientID'";
            
            if (mysqli_query($conn, $update_query)) {
                $_SESSION['insurance_success'] = "Insurance information updated successfully";
            } else {
                logError("Failed to update insurance: " . mysqli_error($conn));
                $_SESSION['insurance_error'] = "Failed to update insurance information";
            }
        } else {
            
            $insert_query = "INSERT INTO Insurance (PatientID, ProviderName, PolicyNumber) 
                            VALUES ('$patientID', '$providerName', '$policyNumber')";
            
            if (mysqli_query($conn, $insert_query)) {
                $_SESSION['insurance_success'] = "Insurance information added successfully";
            } else {
                logError("Failed to add insurance: " . mysqli_error($conn));
                $_SESSION['insurance_error'] = "Failed to add insurance information";
            }
        }
    } elseif ($action === 'update') {
        $insuranceID = mysqli_real_escape_string($conn, $_POST['insuranceID'] ?? '');
        
        if (empty($insuranceID)) {
            $_SESSION['insurance_error'] = "Insurance ID is missing";
            header("Location: index.php");
            exit();
        }
        
        
        $verify_query = "SELECT * FROM Insurance WHERE InsuranceID = '$insuranceID' AND PatientID = '$patientID'";
        $verify_result = mysqli_query($conn, $verify_query);
        
        if (!$verify_result || mysqli_num_rows($verify_result) == 0) {
            logError("Unauthorized insurance update attempt: $insuranceID for patient $patientID");
            $_SESSION['insurance_error'] = "Unauthorized action";
            header("Location: index.php");
            exit();
        }
        
        
        $update_query = "UPDATE Insurance SET ProviderName = '$providerName', PolicyNumber = '$policyNumber' 
                        WHERE InsuranceID = '$insuranceID'";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['insurance_success'] = "Insurance information updated successfully";
        } else {
            logError("Failed to update insurance: " . mysqli_error($conn));
            $_SESSION['insurance_error'] = "Failed to update insurance information";
        }
    } else {
        $_SESSION['insurance_error'] = "Invalid action";
    }
    
    header("Location: index.php");
    exit();
} else {
    
    header("Location: index.php");
    exit();
}
?>