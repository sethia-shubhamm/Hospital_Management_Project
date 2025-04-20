<?php
session_start();
require_once '../../../db_connect.php';  


if (!isset($_SESSION['user_type'])) {
    header("Location: ../../../login.php");  
    exit();
}

$userType = $_SESSION['user_type'];
$userID = $_SESSION['user_id'] ?? 0;
$recordID = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($recordID <= 0) {
    header("Location: view_medical_records.php");
    exit();
}


$accessCondition = "";
if ($userType === 'Doctor') {
    $accessCondition = "AND mr.DoctorID = $userID";
} elseif ($userType === 'Patient') {
    $accessCondition = "AND mr.PatientID = $userID";
}


$query = "SELECT mr.*, p.PatientName, p.PatientAge, p.PatientGender, p.BloodType, d.DoctorName, d.Specialty
          FROM MedicalRecords mr
          JOIN Patients p ON mr.PatientID = p.PatientID
          JOIN Doctors d ON mr.DoctorID = d.DoctorID
          WHERE mr.RecordID = $recordID $accessCondition";

$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) === 0) {
    
    header("Location: view_medical_records.php");
    exit();
}

$record = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Record Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Fullcalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Medical Record Details</h1>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($record['MedicalCondition']); ?></h2>
                <p class="text-muted">Record Date: <?php echo htmlspecialchars($record['RecordDate']); ?></p>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h3>Patient Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($record['PatientName']); ?></p>
                        <p><strong>Age:</strong> <?php echo htmlspecialchars($record['PatientAge']); ?></p>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($record['PatientGender']); ?></p>
                        <p><strong>Blood Type:</strong> <?php echo htmlspecialchars($record['BloodType']); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <h3>Doctor Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($record['DoctorName']); ?></p>
                        <p><strong>Specialty:</strong> <?php echo htmlspecialchars($record['Specialty']); ?></p>
                    </div>
                </div>
                
                <div class="treatment-info mt-4">
                    <h3>Treatment Information</h3>
                    <div class="treatment-text p-3 bg-light rounded">
                        <?php echo nl2br(htmlspecialchars($record['TreatmentInfo'])); ?>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <a href="view_medical_records.php" class="btn btn-secondary">Back to Records</a>
                
                <?php if ($userType === 'Doctor' && $record['DoctorID'] == $userID): ?>
                    <a href="add_medical_record.php?id=<?php echo $record['RecordID']; ?>" class="btn btn-warning">Edit Record</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>