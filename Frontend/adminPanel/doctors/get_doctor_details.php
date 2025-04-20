<?php
session_start();
header('Content-Type: application/json');


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}


require_once '../../../db_connect.php';


if (!isset($_GET['doctorId']) || empty($_GET['doctorId'])) {
    echo json_encode(['success' => false, 'message' => 'Doctor ID is required']);
    exit;
}

$doctorId = intval($_GET['doctorId']);

try {
    
    $stmt = $conn->prepare("SELECT * FROM Doctors WHERE DoctorID = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit;
    }

    $doctor = $result->fetch_assoc();

    
    if (isset($doctor['JoinDate'])) {
        $doctor['JoinDate'] = date('Y-m-d', strtotime($doctor['JoinDate']));
    }

    
    if (isset($doctor['Password'])) {
        unset($doctor['Password']);
    }

    echo json_encode(['success' => true, 'doctor' => $doctor]);

} catch (Exception $e) {
    logError('Error fetching doctor details: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching doctor details']);
    exit;
}
?>