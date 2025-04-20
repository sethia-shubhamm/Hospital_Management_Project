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
    
    $donor_name = mysqli_real_escape_string($conn, $_POST['donor_name'] ?? '');
    $blood_type = mysqli_real_escape_string($conn, $_POST['donor_blood_group'] ?? '');
    $donation_date = mysqli_real_escape_string($conn, $_POST['donor_date'] ?? '');

    
    if (empty($donor_name) || empty($blood_type) || empty($donation_date)) {
        $_SESSION['donation_error'] = "All fields are required";
        header("Location: index.php");
        exit();
    }

    
    $valid_blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (!in_array($blood_type, $valid_blood_types)) {
        $_SESSION['donation_error'] = "Invalid blood type";
        header("Location: index.php");
        exit();
    }

    
    $insert_query = "INSERT INTO BloodDonors (DonorName, BloodType, DonationDate) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sss", $donor_name, $blood_type, $donation_date);

        if (mysqli_stmt_execute($stmt)) {
            
            $donor_id = mysqli_insert_id($conn);

            
            $inventory_check = "SELECT * FROM BloodInventory WHERE BloodType = ?";
            $check_stmt = mysqli_prepare($conn, $inventory_check);
            mysqli_stmt_bind_param($check_stmt, "s", $blood_type);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($result) > 0) {
                
                $update_query = "UPDATE BloodInventory SET Quantity = Quantity + 1 WHERE BloodType = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "s", $blood_type);

                if (!mysqli_stmt_execute($update_stmt)) {
                    logError("Failed to update blood inventory: " . mysqli_error($conn));
                }

                mysqli_stmt_close($update_stmt);
            } else {
                
                $expiry_date = date('Y-m-d', strtotime('+35 days')); 
                $insert_inventory = "INSERT INTO BloodInventory (BloodType, Quantity, ExpiryDate) VALUES (?, 1, ?)";
                $inv_stmt = mysqli_prepare($conn, $insert_inventory);
                mysqli_stmt_bind_param($inv_stmt, "ss", $blood_type, $expiry_date);

                if (!mysqli_stmt_execute($inv_stmt)) {
                    logError("Failed to insert into blood inventory: " . mysqli_error($conn));
                }

                mysqli_stmt_close($inv_stmt);
            }

            mysqli_stmt_close($check_stmt);

            
            $_SESSION['donation_success'] = "Thank you for your donation! Your contribution helps save lives.";
        } else {
            
            logError("Failed to insert donation: " . mysqli_stmt_error($stmt));
            $_SESSION['donation_error'] = "Failed to process your donation. Please try again.";
        }

        mysqli_stmt_close($stmt);
    } else {
        
        logError("Failed to prepare statement: " . mysqli_error($conn));
        $_SESSION['donation_error'] = "System error. Please try again later.";
    }

    
    header("Location: index.php");
    exit();
}


header("Location: index.php");
exit();
?>