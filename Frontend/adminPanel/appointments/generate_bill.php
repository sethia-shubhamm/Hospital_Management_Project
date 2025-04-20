<?php


session_start();


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    $_SESSION['bill_error'] = "Please log in as an administrator to access this page";
    header("Location: ../../adminLogin/index.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['bill_error'] = "Invalid request method";
    header("Location: index.php");
    exit();
}


require_once '../../../db_connect.php';


$patient_id = $_POST['patient_id'] ?? '';
$bill_amount = $_POST['bill_amount'] ?? '';
$appointment_id = $_POST['appointment_id'] ?? '';


logError("Received data - Patient ID: $patient_id, Bill Amount: $bill_amount, Appointment ID: $appointment_id");


if (empty($patient_id) || empty($bill_amount)) {
    logError("Missing required fields");
    $_SESSION['bill_error'] = "Patient ID and Bill Amount are required";
    header("Location: index.php");
    exit();
}


if (!is_numeric($bill_amount) || $bill_amount <= 0) {
    logError("Invalid bill amount: $bill_amount");
    $_SESSION['bill_error'] = "Invalid bill amount";
    header("Location: index.php");
    exit();
}


mysqli_begin_transaction($conn);

try {
    
    $bill_date = date('Y-m-d');

    
    $query = "INSERT INTO Bills (PatientID, BillDate, BillAmount) VALUES (?, ?, ?)";
    logError("Preparing query: $query");

    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }

    
    $patient_id = (int) $patient_id;
    $bill_amount = (float) $bill_amount;

    logError("Binding parameters - PatientID: $patient_id, BillDate: $bill_date, BillAmount: $bill_amount");
    mysqli_stmt_bind_param($stmt, "isd", $patient_id, $bill_date, $bill_amount);

    logError("Executing insert statement");
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to execute statement: " . mysqli_stmt_error($stmt));
    }

    $bill_id = mysqli_insert_id($conn);
    logError("Bill inserted successfully with ID: $bill_id");

    mysqli_stmt_close($stmt);

    
    if (!empty($appointment_id)) {
        
        $check_status_column = "SHOW COLUMNS FROM Appointments LIKE 'Status'";
        $status_result = mysqli_query($conn, $check_status_column);
        $has_status_column = mysqli_num_rows($status_result) > 0;

        if ($has_status_column) {
            
            $update_query = "UPDATE Appointments SET Status = 'Billed' WHERE AppointmentID = ?";
            logError("Preparing update query: $update_query");

            $update_stmt = mysqli_prepare($conn, $update_query);

            if (!$update_stmt) {
                throw new Exception("Failed to prepare update statement: " . mysqli_error($conn));
            }

            $appointment_id = (int) $appointment_id;
            logError("Binding appointment ID parameter: $appointment_id");
            mysqli_stmt_bind_param($update_stmt, "i", $appointment_id);

            logError("Executing update statement");
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Failed to update appointment status: " . mysqli_stmt_error($update_stmt));
            }

            $affected_rows = mysqli_stmt_affected_rows($update_stmt);
            logError("Updated $affected_rows appointment(s) with Billed status");

            mysqli_stmt_close($update_stmt);
        } else {
            logError("Status column not found in Appointments table - skipping status update");
        }
    }

    
    mysqli_commit($conn);

    $_SESSION['bill_success'] = "Bill #$bill_id generated successfully!";

} catch (Exception $e) {
    
    mysqli_rollback($conn);

    logError("Error: " . $e->getMessage());
    $_SESSION['bill_error'] = "Failed to generate bill: " . $e->getMessage();
}


header('Location: index.php');
exit();
?>