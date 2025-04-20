<?php
session_start();
require_once '../../db_connect.php';
require_once '../../log.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : '';

    
    error_log("Login attempt: Email=$email, User Type=$user_type");

    
    $query = "SELECT * FROM LoginCredentials WHERE Email = '$email'";

    
    if (!empty($user_type)) {
        $query .= " AND UserType = '$user_type'";
    }

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        error_log("User found, checking password");

        
        if ($password === $user['UserPassword']) { 
            
            $_SESSION['user_id'] = $user['LoginID'];
            $_SESSION['email'] = $user['Email'];
            $_SESSION['user_type'] = $user['UserType'];
            error_log("Password matched. Redirecting to dashboard for " . $user['UserType']);
            
            
            logUserLogin($user['LoginID'], $user['UserType'], $email);

            
            switch ($user['UserType']) {
                case 'Admin':
                    header("Location: ../adminPanel/dashboard/index.php");
                    exit();
                case 'Doctor':
                    header("Location: ../doctorPage/dashboard/index.php");
                    exit();
                case 'Patient':
                    header("Location: ../patientPage/dashboard/index.php");
                    exit();
                default:
                    header("Location: ../index.php");
                    exit();
            }
        } else {
            
            error_log("Password doesn't match for user: $email");
            $_SESSION['login_error'] = "Invalid password";
            
            
            logFailedLogin($email, "Invalid password");

            
            redirectToLoginPage($user_type);
        }
    } else {
        
        error_log("User not found with email: $email");
        $_SESSION['login_error'] = "User not found";
        
        
        logFailedLogin($email, "User not found");

        
        redirectToLoginPage($user_type);
    }
} else {
    
    header("Location: ../index.php");
    exit();
}


function redirectToLoginPage($user_type)
{
    error_log("Redirecting to login page for user type: $user_type");
    switch ($user_type) {
        case 'Admin':
            header("Location: ../adminLogin/index.php");
            break;
        case 'Doctor':
            header("Location: ../doctorLogin/index.php");
            break;
        case 'Patient':
        default:
            header("Location: index.php");
            break;
    }
    exit();
}

