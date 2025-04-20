<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Patient') {
    $_SESSION['login_error'] = "Please log in as a patient to access this page";
    header("Location: ../../login/index.php");
    exit();
}


function logError($message)
{
    $logFile = "../error_log.txt";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    require_once '../../../db_connect.php';

    
    $loginID = $_SESSION['user_id'];
    $patientID = $loginID; 

    
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $insurance = mysqli_real_escape_string($conn, $_POST['insurance'] ?? '');

    
    if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['account_update_error'] = "New password and confirmation do not match";
            header("Location: index.php");
            exit();
        }

        
        if (strlen($newPassword) < 6) {
            $_SESSION['account_update_error'] = "Password must be at least 6 characters long";
            header("Location: index.php");
            exit();
        }

        
        $passCheckQuery = "SELECT UserPassword FROM LoginCredentials WHERE LoginID = '$loginID'";
        $passCheckResult = mysqli_query($conn, $passCheckQuery);

        if (!$passCheckResult) {
            logError("Password check query failed: " . mysqli_error($conn));
            $_SESSION['account_update_error'] = "Database error. Please try again later.";
            header("Location: index.php");
            exit();
        }

        if (mysqli_num_rows($passCheckResult) > 0) {
            $storedPass = mysqli_fetch_assoc($passCheckResult)['UserPassword'];

            
            if ($storedPass !== $currentPassword) {
                $_SESSION['account_update_error'] = "Current password is incorrect";
                header("Location: index.php");
                exit();
            }

            
            $updatePassQuery = "UPDATE LoginCredentials SET UserPassword = '$newPassword' WHERE LoginID = '$loginID'";

            if (!mysqli_query($conn, $updatePassQuery)) {
                logError("Password update failed: " . mysqli_error($conn));
                $_SESSION['account_update_error'] = "Failed to update password. Please try again.";
                header("Location: index.php");
                exit();
            }

            $_SESSION['password_updated'] = true;
        } else {
            
            logError("No login credentials found for ID: $loginID");
            $_SESSION['account_update_error'] = "Account not found. Please contact support.";
            header("Location: index.php");
            exit();
        }
    }

    
    if (!empty($insurance)) {
        
        $insuranceTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Insurance'");

        if (mysqli_num_rows($insuranceTableCheck) == 0) {
            
            $createInsuranceTable = "CREATE TABLE Insurance (
                InsuranceID INT AUTO_INCREMENT PRIMARY KEY,
                PatientID INT NOT NULL,
                ProviderName VARCHAR(100) NOT NULL,
                PolicyNumber VARCHAR(50),
                FOREIGN KEY (PatientID) REFERENCES Patients(PatientID)
            )";

            if (!mysqli_query($conn, $createInsuranceTable)) {
                logError("Failed to create Insurance table: " . mysqli_error($conn));
            }
        }

        
        $checkInsuranceQuery = "SELECT InsuranceID FROM Insurance WHERE PatientID = '$patientID'";
        $checkInsuranceResult = mysqli_query($conn, $checkInsuranceQuery);

        if (!$checkInsuranceResult) {
            logError("Insurance check query failed: " . mysqli_error($conn));
        } else {
            if (mysqli_num_rows($checkInsuranceResult) > 0) {
                
                $updateInsuranceQuery = "UPDATE Insurance SET ProviderName = '$insurance' WHERE PatientID = '$patientID'";

                if (!mysqli_query($conn, $updateInsuranceQuery)) {
                    logError("Insurance update failed: " . mysqli_error($conn));
                }
            } else {
                
                $addInsuranceQuery = "INSERT INTO Insurance (PatientID, ProviderName) VALUES ('$patientID', '$insurance')";

                if (!mysqli_query($conn, $addInsuranceQuery)) {
                    logError("Insurance insert failed: " . mysqli_error($conn));
                }
            }
        }
    }

    
    $_SESSION['account_update_success'] = "Your account settings have been updated successfully!";
    if (isset($_SESSION['password_updated']) && $_SESSION['password_updated']) {
        $_SESSION['account_update_success'] .= " You will need to use your new password on your next login.";
    }
    header("Location: index.php");
    exit();
} else {
    
    header("Location: index.php");
    exit();
}
?>