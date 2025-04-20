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


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    
    $patientId = mysqli_real_escape_string($conn, $_GET['id']);

    if (empty($patientId)) {
        $response['message'] = "Patient ID is required.";
    } else {
        
        $checkPatientQuery = "SELECT * FROM Patients WHERE PatientID = '$patientId'";
        $patientResult = mysqli_query($conn, $checkPatientQuery);

        if (!$patientResult) {
            logError("Error checking patient: " . mysqli_error($conn));
            $response['message'] = "Database error occurred. Please try again.";
        } elseif (mysqli_num_rows($patientResult) === 0) {
            $response['message'] = "Patient not found.";
        } else {
            
            $checkAppointmentsQuery = "SELECT COUNT(*) as count FROM Appointments WHERE PatientID = '$patientId'";
            $appointmentsResult = mysqli_query($conn, $checkAppointmentsQuery);

            if (!$appointmentsResult) {
                logError("Error checking appointments: " . mysqli_error($conn));
                $response['message'] = "Database error occurred. Please try again.";
            } else {
                $appointmentsData = mysqli_fetch_assoc($appointmentsResult);
                $appointmentCount = $appointmentsData['count'];

                if ($appointmentCount > 0) {
                    
                    $updateStatusQuery = "UPDATE Patients SET Status = 'Inactive' WHERE PatientID = '$patientId'";

                    if (mysqli_query($conn, $updateStatusQuery)) {
                        $response['success'] = true;
                        $response['message'] = "Patient has existing appointments and cannot be fully deleted. Their status has been set to 'Inactive' instead.";
                    } else {
                        logError("Failed to update patient status: " . mysqli_error($conn));
                        $response['message'] = "Failed to update patient status. Please try again.";
                    }
                } else {
                    
                    $deletePatientQuery = "DELETE FROM Patients WHERE PatientID = '$patientId'";

                    if (mysqli_query($conn, $deletePatientQuery)) {
                        $response['success'] = true;
                        $response['message'] = "Patient deleted successfully!";
                    } else {
                        logError("Failed to delete patient: " . mysqli_error($conn));
                        $response['message'] = "Failed to delete patient. Please try again.";
                    }
                }
            }
        }
    }
}


if ($response['success']) {
    $_SESSION['success_message'] = $response['message'];
} else {
    $_SESSION['error_message'] = $response['message'] ?: "An error occurred during the deletion process.";
}


header("Location: index.php");
exit();
?>