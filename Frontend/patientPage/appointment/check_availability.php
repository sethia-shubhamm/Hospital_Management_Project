<?php
session_start();
require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = "../error_log.txt";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


header('Content-Type: application/json');


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Patient') {
    echo json_encode([
        'available' => false,
        'message' => 'You must be logged in as a patient to check availability'
    ]);
    exit;
}


$patientID = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_availability'])) {
    $doctorID = isset($_POST['doctor_id']) ? mysqli_real_escape_string($conn, $_POST['doctor_id']) : null;
    $appointmentDate = isset($_POST['appointment_date']) ? mysqli_real_escape_string($conn, $_POST['appointment_date']) : null;
    $appointmentTime = isset($_POST['appointment_time']) ? mysqli_real_escape_string($conn, $_POST['appointment_time']) : null;

    
    if (empty($doctorID) || empty($appointmentDate) || empty($appointmentTime)) {
        echo json_encode([
            'available' => false,
            'message' => 'Please provide all required information'
        ]);
        exit;
    }

    
    $check_status = mysqli_query($conn, "SHOW COLUMNS FROM Appointments LIKE 'Status'");
    $has_status = mysqli_num_rows($check_status) > 0;

    
    $doctor_check_query = "SELECT COUNT(*) as count FROM Appointments 
                          WHERE DoctorID = '$doctorID' 
                          AND AppointmentDate = '$appointmentDate' 
                          AND AppointmentTime = '$appointmentTime'";

    
    if ($has_status) {
        $doctor_check_query .= " AND Status NOT IN ('Cancelled', 'Completed')";
    }

    $doctor_result = mysqli_query($conn, $doctor_check_query);

    if (!$doctor_result) {
        logError("Error checking doctor availability: " . mysqli_error($conn));
        echo json_encode([
            'available' => false,
            'message' => 'Error checking availability. Please try again.'
        ]);
        exit;
    }

    $doctor_row = mysqli_fetch_assoc($doctor_result);

    if ($doctor_row['count'] > 0) {
        echo json_encode([
            'available' => false,
            'message' => 'This doctor is already booked at the selected time'
        ]);
        exit;
    }

    
    $patient_check_query = "SELECT COUNT(*) as count FROM Appointments 
                           WHERE PatientID = '$patientID' 
                           AND AppointmentDate = '$appointmentDate' 
                           AND AppointmentTime = '$appointmentTime'";

    
    if ($has_status) {
        $patient_check_query .= " AND Status NOT IN ('Cancelled', 'Completed')";
    }

    $patient_result = mysqli_query($conn, $patient_check_query);

    if (!$patient_result) {
        logError("Error checking patient availability: " . mysqli_error($conn));
        echo json_encode([
            'available' => false,
            'message' => 'Error checking your schedule. Please try again.'
        ]);
        exit;
    }

    $patient_row = mysqli_fetch_assoc($patient_result);

    if ($patient_row['count'] > 0) {
        echo json_encode([
            'available' => false,
            'message' => 'You already have an appointment at this time'
        ]);
        exit;
    }

    
    echo json_encode([
        'available' => true,
        'message' => 'This time slot is available'
    ]);
    exit;
} else {
    
    echo json_encode([
        'available' => false,
        'message' => 'Invalid request method'
    ]);
}
?>