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


if (isset($_GET['id'])) {
    $doctorId = mysqli_real_escape_string($conn, $_GET['id']);

    
    $checkDoctorQuery = "SELECT * FROM Doctors WHERE DoctorID = '$doctorId'";
    $doctorResult = mysqli_query($conn, $checkDoctorQuery);

    if (!$doctorResult) {
        logError("Error checking doctor: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Database error occurred. Please try again.";
        header("Location: index.php");
        exit();
    }

    if (mysqli_num_rows($doctorResult) === 0) {
        $_SESSION['error_message'] = "Doctor not found.";
        header("Location: index.php");
        exit();
    }

    $doctorData = mysqli_fetch_assoc($doctorResult);
    $doctorEmail = $doctorData['Email'] ?? null;

    
    $loginId = null;
    if ($doctorEmail) {
        $getLoginIdQuery = "SELECT LoginID FROM LoginCredentials WHERE Email = '$doctorEmail' AND UserType = 'Doctor'";
        $loginResult = mysqli_query($conn, $getLoginIdQuery);

        if ($loginResult && mysqli_num_rows($loginResult) > 0) {
            $loginData = mysqli_fetch_assoc($loginResult);
            $loginId = $loginData['LoginID'];
        }
    }

    
    $checkAppointmentsQuery = "SELECT COUNT(*) as count FROM Appointments WHERE DoctorID = '$doctorId'";
    $appointmentsResult = mysqli_query($conn, $checkAppointmentsQuery);

    if ($appointmentsResult) {
        $appointmentsData = mysqli_fetch_assoc($appointmentsResult);

        if ($appointmentsData['count'] > 0) {
            $_SESSION['error_message'] = "Cannot delete doctor with existing appointments. Please reassign or cancel the appointments first.";
            header("Location: index.php");
            exit();
        }
    }

    
    mysqli_autocommit($conn, FALSE);
    $transactionSuccessful = true;

    
    $deleteDoctorQuery = "DELETE FROM Doctors WHERE DoctorID = '$doctorId'";
    if (!mysqli_query($conn, $deleteDoctorQuery)) {
        logError("Error deleting doctor: " . mysqli_error($conn));
        $transactionSuccessful = false;
    }

    
    if ($transactionSuccessful && $loginId) {
        $deleteLoginQuery = "DELETE FROM LoginCredentials WHERE LoginID = '$loginId'";
        if (!mysqli_query($conn, $deleteLoginQuery)) {
            logError("Error deleting login credentials: " . mysqli_error($conn));
            
        }
    }

    
    if ($transactionSuccessful) {
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Doctor deleted successfully!";
    } else {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Failed to delete doctor. Please try again.";
    }

    
    mysqli_autocommit($conn, TRUE);

} else {
    $_SESSION['error_message'] = "Doctor ID is required.";
}


header("Location: index.php");
exit();
?>