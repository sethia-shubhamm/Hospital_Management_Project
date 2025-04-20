<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Doctor') {
    $_SESSION['login_error'] = "Please log in as a doctor to access this page";
    header("Location: ../../login/index.php");
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, '../error_log.txt');
}


if (isset($_GET['id']) && !empty($_GET['id'])) {
    $appointment_id = $_GET['id'];
    $doctor_id = $_SESSION['user_id'];

    
    $column_check_query = "SHOW COLUMNS FROM Appointments";
    $column_result = mysqli_query($conn, $column_check_query);

    if (!$column_result) {
        logError("Database error checking columns: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Database error occurred, please try again";
        header("Location: index.php");
        exit();
    }

    
    $appointmentIdColumn = 'AppointmentID';
    $doctorIdColumn = 'DoctorID';

    
    $columns = [];
    while ($column = mysqli_fetch_assoc($column_result)) {
        $columns[] = strtolower($column['Field']);
    }

    if (in_array('appointment_id', $columns)) {
        $appointmentIdColumn = 'appointment_id';
    } else if (in_array('appointmentid', $columns)) {
        $appointmentIdColumn = 'appointmentid';
    }

    if (in_array('doctor_id', $columns)) {
        $doctorIdColumn = 'doctor_id';
    }

    
    $check_query = "SELECT * FROM Appointments WHERE $appointmentIdColumn = $appointment_id AND $doctorIdColumn = '$doctor_id'";
    $check_result = mysqli_query($conn, $check_query);

    if (!$check_result) {
        logError("Database error checking appointment: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Database error occurred, please try again";
        header("Location: index.php");
        exit();
    }

    if (mysqli_num_rows($check_result) == 0) {
        $_SESSION['error_message'] = "You are not authorized to cancel this appointment";
        header("Location: index.php");
        exit();
    }

    
    $delete_query = "DELETE FROM Appointments WHERE $appointmentIdColumn = $appointment_id AND $doctorIdColumn = '$doctor_id'";
    $delete_result = mysqli_query($conn, $delete_query);

    if (!$delete_result) {
        logError("Database error deleting appointment: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Failed to delete appointment: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }

    
    $_SESSION['success_message'] = "Appointment deleted successfully";
    header("Location: index.php");
    exit();
} else {
    
    $_SESSION['error_message'] = "No appointment specified";
    header("Location: index.php");
    exit();
}
?>