<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    echo "Unauthorized access";
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>Patient ID is required</div>";
    exit();
}


$patientId = mysqli_real_escape_string($conn, $_GET['id']);


$patientQuery = "SELECT * FROM Patients WHERE PatientID = '$patientId'";
$patientResult = mysqli_query($conn, $patientQuery);

if (!$patientResult) {
    logError("Error fetching patient details: " . mysqli_error($conn));
    echo "<div class='alert alert-danger'>Error fetching patient details</div>";
    exit();
}

if (mysqli_num_rows($patientResult) === 0) {
    echo "<div class='alert alert-danger'>Patient not found</div>";
    exit();
}

$patient = mysqli_fetch_assoc($patientResult);


?>
<div class="form-row">
    <div class="form-group">
        <label for="editPatientName" class="form-label required-field">Patient Name</label>
        <input type="text" id="editPatientName" name="patientName" class="form-control" required
            value="<?php echo htmlspecialchars($patient['PatientName']); ?>">
    </div>
    <div class="form-group">
        <label for="editEmail" class="form-label">Email</label>
        <input type="email" id="editEmail" name="email" class="form-control"
            value="<?php echo htmlspecialchars($patient['Email'] ?? ''); ?>">
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="editPhone" class="form-label">Phone Number</label>
        <input type="tel" id="editPhone" name="phone" class="form-control"
            value="<?php echo htmlspecialchars($patient['Phone'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="editAge" class="form-label">Age</label>
        <input type="number" id="editAge" name="age" class="form-control" min="0" max="120"
            value="<?php echo $patient['Age'] ?? ''; ?>">
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="editGender" class="form-label">Gender</label>
        <select id="editGender" name="gender" class="form-control">
            <option value="">Select Gender</option>
            <option value="Male" <?php echo (isset($patient['Gender']) && $patient['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo (isset($patient['Gender']) && $patient['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
            <option value="Other" <?php echo (isset($patient['Gender']) && $patient['Gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
        </select>
    </div>
    <div class="form-group">
        <label for="editBloodType" class="form-label">Blood Type</label>
        <select id="editBloodType" name="bloodType" class="form-control">
            <option value="">Select Blood Type</option>
            <option value="A+" <?php echo (isset($patient['BloodType']) && $patient['BloodType'] == 'A+') ? 'selected' : ''; ?>>A+</option>
            <option value="A-" <?php echo (isset($patient['BloodType']) && $patient['BloodType'] == 'A-') ? 'selected' : ''; ?>>A-</option>
            <option value="B+" <?php echo (isset($patient['BloodType']) && $patient['BloodType'] == 'B+') ? 'selected' : ''; ?>>B+</option>
            <option value="B-" <?php echo (isset($patient['BloodType']) && $patient['BloodType'] == 'B-') ? 'selected' : ''; ?>>B-</option>
            <option value="AB+" <?php echo (isset($patient['BloodType']) && $patient['BloodType'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
            <option value="AB-" <?php echo (isset($patient['BloodType']) && $patient['BloodType'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
            <option value="O+" <?php echo (isset($patient['BloodType']) && $patient['BloodType'] == 'O+') ? 'selected' : ''; ?>>O+</option>
            <option value="O-" <?php echo (isset($patient['BloodType']) && $patient['BloodType'] == 'O-') ? 'selected' : ''; ?>>O-</option>
        </select>
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="editStatus" class="form-label">Status</label>
        <select id="editStatus" name="status" class="form-control">
            <option value="Active" <?php echo (!isset($patient['Status']) || $patient['Status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
            <option value="Admitted" <?php echo (isset($patient['Status']) && $patient['Status'] == 'Admitted') ? 'selected' : ''; ?>>Admitted</option>
            <option value="Outpatient" <?php echo (isset($patient['Status']) && $patient['Status'] == 'Outpatient') ? 'selected' : ''; ?>>Outpatient</option>
            <option value="Emergency" <?php echo (isset($patient['Status']) && $patient['Status'] == 'Emergency') ? 'selected' : ''; ?>>Emergency</option>
            <option value="Discharged" <?php echo (isset($patient['Status']) && $patient['Status'] == 'Discharged') ? 'selected' : ''; ?>>Discharged</option>
            <option value="Inactive" <?php echo (isset($patient['Status']) && $patient['Status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
        </select>
    </div>
    <div class="form-group">
        <label for="editRegisterDate" class="form-label">Registration Date</label>
        <input type="date" id="editRegisterDate" name="registerDate" class="form-control"
            value="<?php echo isset($patient['RegisterDate']) ? date('Y-m-d', strtotime($patient['RegisterDate'])) : date('Y-m-d'); ?>">
    </div>
</div>
<div class="form-row">
    <div class="form-group" style="flex: 0 0 100%;">
        <label for="editAddress" class="form-label">Address</label>
        <textarea id="editAddress" name="address" class="form-control"
            rows="2"><?php echo htmlspecialchars($patient['Address'] ?? ''); ?></textarea>
    </div>
</div>
<div class="form-row">
    <div class="form-group" style="flex: 0 0 100%;">
        <label for="editMedicalHistory" class="form-label">Medical History</label>
        <textarea id="editMedicalHistory" name="medicalHistory" class="form-control"
            rows="3"><?php echo htmlspecialchars($patient['MedicalHistory'] ?? ''); ?></textarea>
    </div>
</div>
<div class="form-row">
    <div class="form-group">
        <label for="editEmergencyContact" class="form-label">Emergency Contact Name</label>
        <input type="text" id="editEmergencyContact" name="emergencyContact" class="form-control"
            value="<?php echo htmlspecialchars($patient['EmergencyContact'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="editEmergencyPhone" class="form-label">Emergency Contact Phone</label>
        <input type="tel" id="editEmergencyPhone" name="emergencyPhone" class="form-control"
            value="<?php echo htmlspecialchars($patient['EmergencyPhone'] ?? ''); ?>">
    </div>
</div>