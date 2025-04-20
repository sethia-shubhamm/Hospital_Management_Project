<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


error_log("Signup process started at " . date('Y-m-d H:i:s'));

require_once '../../db_connect.php';
require_once '../../log.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    error_log("POST data received: " . print_r($_POST, true));

    
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; 
    $age = isset($_POST['age']) ? (int) $_POST['age'] : null;
    $gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : null;
    $blood_type = isset($_POST['blood_type']) ? mysqli_real_escape_string($conn, $_POST['blood_type']) : null;
    $contact_info = isset($_POST['contact_info']) ? mysqli_real_escape_string($conn, $_POST['contact_info']) : null;
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : null;
    $user_type = isset($_POST['user_type']) ? mysqli_real_escape_string($conn, $_POST['user_type']) : 'Patient';

    
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['signup_error'] = "Please fill in all required fields";
        logSystemActivity("Failed registration attempt: Missing required fields for email: $email");
        header("Location: index.php");
        mysqli_close($conn);
        exit();
    }

    
    $check_query = "SELECT * FROM LoginCredentials WHERE Email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $_SESSION['signup_error'] = "Email already exists. Please use a different email or login.";
        logSystemActivity("Failed registration attempt: Email already exists: $email");
        header("Location: index.php");
        mysqli_close($conn);
        exit();
    }

    
    mysqli_begin_transaction($conn);

    try {
        
        $login_query = "INSERT INTO LoginCredentials (Email, UserPassword, UserType) 
                      VALUES ('$email', '$password', '$user_type')";

        if (!mysqli_query($conn, $login_query)) {
            throw new Exception("Error creating login credentials: " . mysqli_error($conn));
        }

        
        $login_id = mysqli_insert_id($conn);

        
        if ($user_type == 'Patient') {
            
            $patient_query = "INSERT INTO Patients (PatientID, PatientName, PatientAge, PatientGender, BloodType, ContactInfo) 
                            VALUES ('$login_id', '$name', " . ($age ? $age : "NULL") . ", " .
                ($gender ? "'$gender'" : "NULL") . ", " .
                ($blood_type ? "'$blood_type'" : "NULL") . ", " .
                ($contact_info ? "'$contact_info'" : "NULL") . ")";

            if (!mysqli_query($conn, $patient_query)) {
                throw new Exception("Error creating patient record: " . mysqli_error($conn));
            }

            
            $check_details_table = mysqli_query($conn, "SHOW TABLES LIKE 'PatientDetails'");
            if (mysqli_num_rows($check_details_table) > 0 && !empty($address)) {
                $details_query = "INSERT INTO PatientDetails (PatientID, Address) VALUES ('$login_id', '$address')";
                mysqli_query($conn, $details_query);
            }
        }

        
        mysqli_commit($conn);

        
        $_SESSION['user_id'] = $login_id;
        $_SESSION['email'] = $email;
        $_SESSION['user_type'] = $user_type;

        
        $_SESSION['signup_success'] = "Registration successful! You are now logged in.";

        
        logSystemActivity("New user registration: $user_type ($email) registered successfully");

        
        error_log("Registration successful for user: $email, redirecting to dashboard");

        
        if ($user_type == 'Patient') {
            header("Location: ../patientPage/dashboard/index.php");
        } else {
            header("Location: ../login/index.php");
        }
        mysqli_close($conn);
        exit();

    } catch (Exception $e) {
        
        mysqli_rollback($conn);

        error_log("Signup error: " . $e->getMessage());
        $_SESSION['signup_error'] = "Registration failed: " . $e->getMessage();
        
        
        logSystemActivity("Registration failed for $email: " . $e->getMessage());
        
        header("Location: index.php");
        mysqli_close($conn);
        exit();
    }
} else {
    
    error_log("Attempted to access signup_process.php without POST data");
    header("Location: index.php");
    mysqli_close($conn);
    exit();
}
?>