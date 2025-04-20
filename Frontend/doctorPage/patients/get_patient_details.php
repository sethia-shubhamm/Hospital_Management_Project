<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Doctor') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}


require_once '../../../db_connect.php';


if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit();
}

$patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);
$doctor_id = $_SESSION['user_id'];


$access_check = "SELECT COUNT(*) as count FROM Appointments 
                WHERE DoctorID = '$doctor_id' AND PatientID = '$patient_id'";
$access_result = mysqli_query($conn, $access_check);

if ($access_result && $row = mysqli_fetch_assoc($access_result)) {
    if ($row['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'You do not have access to this patient']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error checking access']);
    exit();
}


$patient_query = "SELECT p.*,
                 (SELECT MAX(AppointmentDate) FROM Appointments 
                  WHERE PatientID = p.PatientID AND DoctorID = '$doctor_id') as LastVisit,
                 (SELECT AppointmentDate FROM Appointments 
                  WHERE PatientID = p.PatientID AND DoctorID = '$doctor_id' AND AppointmentDate >= CURDATE() 
                  ORDER BY AppointmentDate ASC LIMIT 1) as NextAppointment
                 FROM Patients p
                 WHERE p.PatientID = '$patient_id'";
$patient_result = mysqli_query($conn, $patient_query);

if ($patient_result && mysqli_num_rows($patient_result) > 0) {
    $patient = mysqli_fetch_assoc($patient_result);

    echo json_encode([
        'success' => true,
        'patient' => $patient
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Patient not found']);
}

mysqli_close($conn);
?>