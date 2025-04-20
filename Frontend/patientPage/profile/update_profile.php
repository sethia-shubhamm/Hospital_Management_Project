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

    
    $patientID = $_SESSION['user_id'];

    
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName'] ?? '');
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName'] ?? '');
    $fullName = trim("$firstName $lastName");
    $age = !empty($_POST['age']) ? (int) $_POST['age'] : null;
    $gender = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $bloodType = mysqli_real_escape_string($conn, $_POST['bloodType'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $state = mysqli_real_escape_string($conn, $_POST['state'] ?? '');
    $zipCode = mysqli_real_escape_string($conn, $_POST['zipCode'] ?? '');
    $allergies = mysqli_real_escape_string($conn, $_POST['allergies'] ?? '');
    $emergencyContact = mysqli_real_escape_string($conn, $_POST['emergencyContact'] ?? '');
    $emergencyPhone = mysqli_real_escape_string($conn, $_POST['emergencyPhone'] ?? '');

    
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Patients'");
    if (mysqli_num_rows($tableCheck) == 0) {
        
        $createTable = "CREATE TABLE Patients (
            PatientID INT PRIMARY KEY,
            PatientName VARCHAR(100) NOT NULL,
            PatientAge INT NULL,
            PatientGender VARCHAR(10) NULL,
            BloodType VARCHAR(5) NULL,
            ContactInfo VARCHAR(100) NULL
        )";
        if (!mysqli_query($conn, $createTable)) {
            logError("Failed to create Patients table: " . mysqli_error($conn));
            $_SESSION['profile_update_error'] = "Database error occurred. Please try again later.";
            header("Location: index.php");
            exit();
        }
    }

    
    $updateQuery = "UPDATE Patients SET 
                    PatientName = '$fullName',
                    PatientAge = " . ($age === null ? "NULL" : $age) . ",
                    PatientGender = " . (empty($gender) ? "NULL" : "'$gender'") . ",
                    BloodType = " . (empty($bloodType) ? "NULL" : "'$bloodType'") . ",
                    ContactInfo = " . (empty($phone) ? "NULL" : "'$phone'") . "
                    WHERE PatientID = '$patientID'";

    $updateResult = mysqli_query($conn, $updateQuery);

    if (!$updateResult) {
        logError("Failed to update patient record: " . mysqli_error($conn));
        $_SESSION['profile_update_error'] = "Failed to update your profile. Please try again.";
        header("Location: index.php");
        exit();
    }

    
    $detailsTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'PatientDetails'");

    if (mysqli_num_rows($detailsTableCheck) == 0) {
        
        $createDetailsTable = "CREATE TABLE PatientDetails (
            DetailID INT AUTO_INCREMENT PRIMARY KEY,
            PatientID INT NOT NULL,
            FirstName VARCHAR(50) NULL,
            LastName VARCHAR(50) NULL,
            Address VARCHAR(100) NULL,
            City VARCHAR(50) NULL,
            State VARCHAR(30) NULL,
            ZipCode VARCHAR(20) NULL,
            EmergencyContact VARCHAR(100) NULL,
            EmergencyPhone VARCHAR(20) NULL,
            Allergies TEXT NULL,
            FOREIGN KEY (PatientID) REFERENCES Patients(PatientID)
        )";

        if (!mysqli_query($conn, $createDetailsTable)) {
            logError("Failed to create PatientDetails table: " . mysqli_error($conn));
            
        }
    }

    
    $checkDetailsQuery = "SELECT DetailID FROM PatientDetails WHERE PatientID = '$patientID'";
    $checkDetailsResult = mysqli_query($conn, $checkDetailsQuery);

    if (!$checkDetailsResult) {
        logError("Failed to check for existing patient details: " . mysqli_error($conn));
    } else {
        if (mysqli_num_rows($checkDetailsResult) > 0) {
            
            $updateDetailsQuery = "UPDATE PatientDetails SET 
                            FirstName = '$firstName',
                            LastName = '$lastName',
                            Address = '$address',
                            City = '$city',
                            State = '$state',
                            ZipCode = '$zipCode',
                            EmergencyContact = '$emergencyContact',
                            EmergencyPhone = '$emergencyPhone',
                            Allergies = '$allergies'
                            WHERE PatientID = '$patientID'";

            if (!mysqli_query($conn, $updateDetailsQuery)) {
                logError("Failed to update patient details: " . mysqli_error($conn));
            }
        } else {
            
            $insertDetailsQuery = "INSERT INTO PatientDetails 
                            (PatientID, FirstName, LastName, Address, City, State, ZipCode, EmergencyContact, EmergencyPhone, Allergies)
                            VALUES 
                            ('$patientID', '$firstName', '$lastName', '$address', '$city', '$state', '$zipCode', '$emergencyContact', '$emergencyPhone', '$allergies')";

            if (!mysqli_query($conn, $insertDetailsQuery)) {
                logError("Failed to insert patient details: " . mysqli_error($conn));
            }
        }
    }

    
    $_SESSION['profile_update_success'] = "Your profile has been updated successfully!";
    header("Location: index.php");
    exit();
} else {
    
    header("Location: index.php");
    exit();
}
?>