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
    
    $doctorName = mysqli_real_escape_string($conn, $_POST['doctorName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : '';
    $specialty = isset($_POST['specialty']) ? mysqli_real_escape_string($conn, $_POST['specialty']) : '';
    $qualification = isset($_POST['qualification']) ? mysqli_real_escape_string($conn, $_POST['qualification']) : '';
    $password = $_POST['password'];
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'Active';
    $joinDate = date('Y-m-d'); 

    
    if (empty($doctorName) || empty($email) || empty($password)) {
        $response['message'] = "Required fields cannot be empty";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email format";
    } else {
        
        $loginTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'LoginCredentials'");

        if (mysqli_num_rows($loginTableCheck) == 0) {
            
            $createLoginTable = "CREATE TABLE LoginCredentials (
                LoginID INT AUTO_INCREMENT PRIMARY KEY,
                Email VARCHAR(100) UNIQUE NOT NULL,
                UserPassword VARCHAR(255) NOT NULL,
                UserType VARCHAR(20) NOT NULL,
                CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";

            if (!mysqli_query($conn, $createLoginTable)) {
                logError("Failed to create LoginCredentials table: " . mysqli_error($conn));
                $response['message'] = "Database error occurred. Please try again.";
                echo json_encode($response);
                exit();
            }
        }

        
        $checkEmailQuery = "SELECT * FROM LoginCredentials WHERE Email = '$email'";
        $emailResult = mysqli_query($conn, $checkEmailQuery);

        if (!$emailResult) {
            logError("Database error checking email: " . mysqli_error($conn));
            $response['message'] = "Database error occurred. Please try again.";
        } elseif (mysqli_num_rows($emailResult) > 0) {
            $response['message'] = "Email already exists. Please use a different email.";
        } else {
            
            mysqli_autocommit($conn, FALSE);
            $transactionSuccessful = true;

            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            
            $loginQuery = "INSERT INTO LoginCredentials (Email, UserPassword, UserType) 
                          VALUES ('$email', '$hashedPassword', 'Doctor')";

            if (!mysqli_query($conn, $loginQuery)) {
                logError("Failed to create login record: " . mysqli_error($conn));
                $response['message'] = "Failed to create doctor account. Please try again.";
                $transactionSuccessful = false;
            }

            if ($transactionSuccessful) {
                
                $loginID = mysqli_insert_id($conn);

                
                $doctorQuery = "INSERT INTO Doctors (DoctorName, Email, Phone, Specialty, Qualification, JoinDate, Status) 
                              VALUES ('$doctorName', '$email', '$phone', '$specialty', '$qualification', '$joinDate', '$status')";

                if (!mysqli_query($conn, $doctorQuery)) {
                    logError("Failed to create doctor record: " . mysqli_error($conn));
                    $response['message'] = "Failed to create doctor record. Please try again.";
                    $transactionSuccessful = false;
                }
            }

            
            if ($transactionSuccessful) {
                mysqli_commit($conn);
                $response['success'] = true;
                $response['message'] = "Doctor account created successfully!";
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