<?php
session_start();
require_once '../../../db_connect.php'; 


if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: ../../../login.php");
    exit();
}

$doctorID = $_SESSION['user_id'] ?? 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $patientID = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $medicalCondition = mysqli_real_escape_string($conn, $_POST['medical_condition']);
    $treatmentInfo = mysqli_real_escape_string($conn, $_POST['treatment_info']);
    $recordDate = mysqli_real_escape_string($conn, $_POST['record_date']);
    $recordID = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
    
    
    if ($patientID <= 0 || empty($medicalCondition) || empty($treatmentInfo) || empty($recordDate)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: add_medical_record.php" . ($recordID ? "?id=$recordID" : ""));
        exit();
    }
    
    if ($recordID > 0) {
        
        $updateQuery = "UPDATE MedicalRecords 
                        SET PatientID = $patientID, 
                            MedicalCondition = '$medicalCondition', 
                            TreatmentInfo = '$treatmentInfo', 
                            RecordDate = '$recordDate' 
                        WHERE RecordID = $recordID AND DoctorID = $doctorID";
        
        if (mysqli_query($conn, $updateQuery)) {
            $_SESSION['success'] = "Medical record updated successfully.";
        } else {
            $_SESSION['error'] = "Error updating record: " . mysqli_error($conn);
        }
    } else {
        
        $insertQuery = "INSERT INTO MedicalRecords 
                        (PatientID, MedicalCondition, TreatmentInfo, DoctorID, RecordDate) 
                        VALUES 
                        ($patientID, '$medicalCondition', '$treatmentInfo', $doctorID, '$recordDate')";
        
        if (mysqli_query($conn, $insertQuery)) {
            $_SESSION['success'] = "Medical record added successfully.";
        } else {
            $_SESSION['error'] = "Error adding record: " . mysqli_error($conn);
        }
    }
    
    header("Location: view_medical_records.php");
    exit();
} else {
    
    header("Location: add_medical_record.php");
    exit();
}
?>