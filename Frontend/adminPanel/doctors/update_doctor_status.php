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


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}


if (!isset($_POST['doctorId']) || empty($_POST['doctorId']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Doctor ID and status are required']);
    exit;
}

$doctorId = intval($_POST['doctorId']);
$status = $_POST['status'];


$validStatuses = ['Active', 'Inactive'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    
    $stmt = $conn->prepare("UPDATE Doctors SET Status = ? WHERE DoctorID = ?");
    $stmt->bind_param("si", $status, $doctorId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Doctor status updated successfully']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made or doctor not found']);
            exit;
        }
    } else {
        throw new Exception("Error executing query: " . $stmt->error);
    }

} catch (Exception $e) {
    logError('Error updating doctor status: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating doctor status']);
    exit;
}
?>