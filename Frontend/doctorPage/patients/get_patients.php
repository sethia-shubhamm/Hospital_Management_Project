<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Doctor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, '../error_log.txt');
}


$doctor_id = $_SESSION['user_id'];


$query = "SELECT DISTINCT p.* 
          FROM Patients p
          JOIN Appointments a ON p.PatientID = a.PatientID
          WHERE a.DoctorID = '$doctor_id'
          ORDER BY p.PatientName";
$result = mysqli_query($conn, $query);

if (!$result) {
    logError("Database error getting patients: " . mysqli_error($conn));
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

$patients = [];

while ($patient = mysqli_fetch_assoc($result)) {
    
    if (isset($patient['DateOfBirth'])) {
        $dob = new DateTime($patient['DateOfBirth']);
        $now = new DateTime();
        $interval = $now->diff($dob);
        $patient['Age'] = $interval->y;
    } else if (isset($patient['Age'])) {
        
    } else {
        $patient['Age'] = null;
    }

    
    $last_visit_query = "SELECT MAX(AppointmentDate) as LastVisit 
                         FROM Appointments 
                         WHERE PatientID = '{$patient['PatientID']}' 
                         AND AppointmentDate <= CURDATE()";
    $last_visit_result = mysqli_query($conn, $last_visit_query);

    if ($last_visit_result && mysqli_num_rows($last_visit_result) > 0) {
        $last_visit = mysqli_fetch_assoc($last_visit_result);
        $patient['LastVisit'] = $last_visit['LastVisit'];
    } else {
        $patient['LastVisit'] = null;
    }

    
    $patient['Status'] = isset($patient['Status']) ? $patient['Status'] : 'Active';

    $patients[] = $patient;
}


header('Content-Type: application/json');
echo json_encode(['success' => true, 'patients' => $patients]);
?>