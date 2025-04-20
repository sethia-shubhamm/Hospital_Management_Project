<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    $_SESSION['login_error'] = "Please log in as an administrator to access this page";
    header("Location: ../../adminLogin/index.php");
    exit();
}


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


require_once '../../../db_connect.php';


$response = [
    'success' => false,
    'message' => ''
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $doctorId = isset($_POST['doctorId']) ? mysqli_real_escape_string($conn, $_POST['doctorId']) : '';
    $doctorName = mysqli_real_escape_string($conn, $_POST['doctorName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : '';
    $specialty = isset($_POST['specialty']) ? mysqli_real_escape_string($conn, $_POST['specialty']) : '';
    $qualification = isset($_POST['qualification']) ? mysqli_real_escape_string($conn, $_POST['qualification']) : '';
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'Active';
    $updatePassword = isset($_POST['updatePassword']) && $_POST['updatePassword'] == '1';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    
    if (empty($doctorId) || empty($doctorName) || empty($email)) {
        $response['message'] = "Required fields cannot be empty";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format";
    } else {
        
        $getDoctorQuery = "SELECT * FROM Doctors WHERE DoctorID = '$doctorId'";
        $doctorResult = mysqli_query($conn, $getDoctorQuery);

        if (!$doctorResult || mysqli_num_rows($doctorResult) === 0) {
            $response['message'] = "Doctor not found";
        } else {
            $doctorData = mysqli_fetch_assoc($doctorResult);

            
            $doctorEmail = $doctorData['Email'];

            
            $findLoginQuery = "SELECT LoginID FROM LoginCredentials WHERE Email = '$doctorEmail'";
            $loginResult = mysqli_query($conn, $findLoginQuery);

            if ($loginResult && mysqli_num_rows($loginResult) > 0) {
                $loginData = mysqli_fetch_assoc($loginResult);
                $loginID = $loginData['LoginID'];
            } else {
                
                $createLoginQuery = "INSERT INTO LoginCredentials (Email, UserPassword, UserType) 
                                   VALUES ('$email', '" . password_hash('temp123', PASSWORD_DEFAULT) . "', 'Doctor')";
                if (mysqli_query($conn, $createLoginQuery)) {
                    $loginID = mysqli_insert_id($conn);
                } else {
                    $response['message'] = "Failed to create login credentials.";
                    $transactionSuccessful = false;
                }
            }

            
            mysqli_autocommit($conn, FALSE);
            $transactionSuccessful = true;

            
            if ($email != $doctorData['Email']) {
                
                $checkEmailQuery = "SELECT * FROM LoginCredentials WHERE Email = '$email' AND LoginID != '$loginID'";
                $emailResult = mysqli_query($conn, $checkEmailQuery);

                if (!$emailResult) {
                    logError("Database error checking email: " . mysqli_error($conn));
                    $response['message'] = "Database error occurred. Please try again.";
                    $transactionSuccessful = false;
                } elseif (mysqli_num_rows($emailResult) > 0) {
                    $response['message'] = "Email already exists. Please use a different email.";
                    $transactionSuccessful = false;
                } else {
                    $updateLoginQuery = "UPDATE LoginCredentials SET Email = '$email' WHERE LoginID = '$loginID'";
                    if (!mysqli_query($conn, $updateLoginQuery)) {
                        logError("Failed to update login record: " . mysqli_error($conn));
                        $response['message'] = "Failed to update doctor account. Please try again.";
                        $transactionSuccessful = false;
                    }
                }
            }

            
            if ($transactionSuccessful && $updatePassword && !empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updatePasswordQuery = "UPDATE LoginCredentials SET UserPassword = '$hashedPassword' WHERE LoginID = '$loginID'";

                if (!mysqli_query($conn, $updatePasswordQuery)) {
                    logError("Failed to update password: " . mysqli_error($conn));
                    $response['message'] = "Failed to update password. Please try again.";
                    $transactionSuccessful = false;
                }
            }

            
            if ($transactionSuccessful) {
                $updateDoctorQuery = "UPDATE Doctors SET 
                    DoctorName = '$doctorName',
                    Email = '$email',
                    Phone = '$phone',
                    Specialty = '$specialty',
                    Qualification = '$qualification',
                    Status = '$status'
                    WHERE DoctorID = '$doctorId'";

                if (!mysqli_query($conn, $updateDoctorQuery)) {
                    logError("Failed to update doctor record: " . mysqli_error($conn));
                    $response['message'] = "Failed to update doctor record. Please try again.";
                    $transactionSuccessful = false;
                }
            }

            
            if ($transactionSuccessful) {
                mysqli_commit($conn);
                $response['success'] = true;
                $response['message'] = "Doctor information updated successfully!";
            } else {
                mysqli_rollback($conn);
            }

            
            mysqli_autocommit($conn, TRUE);
        }
    }
}


if ($response['success']) {
    $_SESSION['success_message'] = $response['message'];
} else {
    $_SESSION['error_message'] = $response['message'];
}


header("Location: index.php");
exit();
?>