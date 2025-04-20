<?php
session_start();
require_once '../../../db_connect.php'; 


if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: ../../../login.php");  
    exit();
}

$doctorID = $_SESSION['user_id'] ?? 0;
$patients = [];
$record = null;
$recordID = isset($_GET['id']) ? intval($_GET['id']) : 0;


$patientQuery = "SELECT PatientID, PatientName FROM Patients ORDER BY PatientName";
$patientResult = mysqli_query($conn, $patientQuery);
while ($row = mysqli_fetch_assoc($patientResult)) {
    $patients[] = $row;
}


if ($recordID > 0) {
    $recordQuery = "SELECT * FROM MedicalRecords WHERE RecordID = $recordID AND DoctorID = $doctorID";
    $recordResult = mysqli_query($conn, $recordQuery);
    $record = mysqli_fetch_assoc($recordResult);
    
    
    if (!$record) {
        header("Location: view_medical_records.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $recordID ? 'Edit' : 'Add'; ?> Medical Record</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Fullcalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- Removed the navbar include since it doesn't exist -->
    
    <div class="container mt-4">
        <h1><?php echo $recordID ? 'Edit' : 'Add'; ?> Medical Record</h1>
        
        <form action="process_medical_record.php" method="post">
            <?php if ($recordID): ?>
                <input type="hidden" name="record_id" value="<?php echo $recordID; ?>">
            <?php endif; ?>
            
            <div class="form-group mb-3">
                <label for="patient" class="form-label">Patient:</label>
                <select name="patient_id" id="patient" class="form-select" required>
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo $patient['PatientID']; ?>" <?php echo ($record && $record['PatientID'] == $patient['PatientID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($patient['PatientName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mb-3">
                <label for="condition" class="form-label">Medical Condition:</label>
                <input type="text" name="medical_condition" id="condition" class="form-control" required 
                       value="<?php echo $record ? htmlspecialchars($record['MedicalCondition']) : ''; ?>">
            </div>
            
            <div class="form-group mb-3">
                <label for="treatment" class="form-label">Treatment Information:</label>
                <textarea name="treatment_info" id="treatment" class="form-control" rows="5" required><?php echo $record ? htmlspecialchars($record['TreatmentInfo']) : ''; ?></textarea>
            </div>
            
            <div class="form-group mb-3">
                <label for="date" class="form-label">Record Date:</label>
                <input type="date" name="record_date" id="date" class="form-control" required 
                       value="<?php echo $record ? $record['RecordDate'] : date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <?php echo $recordID ? 'Update' : 'Add'; ?> Medical Record
                </button>
                <a href="view_medical_records.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>