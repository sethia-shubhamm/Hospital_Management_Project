<?php
session_start();


require_once '../../db_connect.php';


function logError($message)
{
    $logFile = 'error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $patient_name = mysqli_real_escape_string($conn, $_POST['patient_name'] ?? '');
    $blood_type = mysqli_real_escape_string($conn, $_POST['required_blood_group'] ?? '');
    $quantity = isset($_POST['blood_units']) ? (int) $_POST['blood_units'] : 0;

    
    if (empty($patient_name) || empty($blood_type) || $quantity <= 0) {
        $_SESSION['request_error'] = "All fields are required and quantity must be greater than zero";
        header("Location: index.php");
        exit();
    }

    
    $valid_blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (!in_array($blood_type, $valid_blood_types)) {
        $_SESSION['request_error'] = "Invalid blood type";
        header("Location: index.php");
        exit();
    }

    
    $patient_id = null;
    $patient_check = "SELECT PatientID FROM Patients WHERE PatientName = ?";
    $check_stmt = mysqli_prepare($conn, $patient_check);

    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, "s", $patient_name);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $patient_id = $row['PatientID'];
        }

        mysqli_stmt_close($check_stmt);
    }

    
    $inventory_check = "SELECT * FROM BloodInventory WHERE BloodType = ?";
    $inv_stmt = mysqli_prepare($conn, $inventory_check);
    mysqli_stmt_bind_param($inv_stmt, "s", $blood_type);
    mysqli_stmt_execute($inv_stmt);
    $inv_result = mysqli_stmt_get_result($inv_stmt);

    $availability = false;
    $available_quantity = 0;

    if (mysqli_num_rows($inv_result) > 0) {
        $inventory = mysqli_fetch_assoc($inv_result);
        $available_quantity = $inventory['Quantity'];

        if ($available_quantity >= $quantity) {
            $availability = true;
        }
    }

    mysqli_stmt_close($inv_stmt);

    
    $status = $availability ? 'Approved' : 'Pending';

    
    $insert_query = "INSERT INTO BloodRequests (PatientID, BloodType, Quantity, RequestStatus) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isis", $patient_id, $blood_type, $quantity, $status);

        if (mysqli_stmt_execute($stmt)) {
            $request_id = mysqli_insert_id($conn);

            
            if ($availability) {
                $update_inventory = "UPDATE BloodInventory SET Quantity = Quantity - ? WHERE BloodType = ?";
                $update_stmt = mysqli_prepare($conn, $update_inventory);
                mysqli_stmt_bind_param($update_stmt, "is", $quantity, $blood_type);

                if (!mysqli_stmt_execute($update_stmt)) {
                    logError("Failed to update inventory: " . mysqli_stmt_error($update_stmt));
                }

                mysqli_stmt_close($update_stmt);
            }

            
            $reference_number = 'REQ-' . str_pad($request_id, 4, '0', STR_PAD_LEFT);
            $_SESSION['request_success'] = "Your blood request has been submitted. Reference number: $reference_number. Status: $status";

            if (!$availability) {
                $_SESSION['request_success'] .= ". We have limited inventory for this blood type. We will contact you when it becomes available.";
            }
        } else {
            
            logError("Failed to insert blood request: " . mysqli_stmt_error($stmt));
            $_SESSION['request_error'] = "Failed to process your request. Please try again.";
        }

        mysqli_stmt_close($stmt);
    } else {
        
        logError("Failed to prepare statement: " . mysqli_error($conn));
        $_SESSION['request_error'] = "System error. Please try again later.";
    }

    
    header("Location: index.php");
    exit();
}


header("Location: index.php");
exit();
?>