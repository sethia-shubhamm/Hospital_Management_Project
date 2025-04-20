<?php
session_start();
require_once '../../../db_connect.php';


if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Doctor') {
    header("Location: ../../../login.php");
    exit();
}

$userID = $_SESSION['user_id'] ?? 0;
$records = [];


$query = "SELECT mr.*, p.PatientName 
          FROM MedicalRecords mr
          JOIN Patients p ON mr.PatientID = p.PatientID
          WHERE mr.DoctorID = $userID
          ORDER BY mr.RecordDate DESC";

$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $records[] = $row;
}


if (isset($_GET['delete'])) {
    $recordID = intval($_GET['delete']);
    $deleteQuery = "DELETE FROM MedicalRecords WHERE RecordID = $recordID AND DoctorID = $userID";
    
    if (mysqli_query($conn, $deleteQuery)) {
        $_SESSION['success'] = "Medical record deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting record: " . mysqli_error($conn);
    }
    
    header("Location: view_medical_records.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records</title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Medical Records</h1>
            <a href="add_medical_record.php" class="btn btn-primary">Add New Medical Record</a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($records)): ?>
            <div class="alert alert-info">No medical records found. Start by adding a new record.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Patient Name</th>
                            <th>Medical Condition</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['PatientName']); ?></td>
                                <td><?php echo htmlspecialchars($record['MedicalCondition']); ?></td>
                                <td><?php echo htmlspecialchars($record['RecordDate']); ?></td>
                                <td>
                                    <a href="view_record_details.php?id=<?php echo $record['RecordID']; ?>" class="btn btn-info btn-sm">View</a>
                                    <a href="add_medical_record.php?id=<?php echo $record['RecordID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="view_medical_records.php?delete=<?php echo $record['RecordID']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>