<?php



require_once 'db_connect.php';


function logError($message)
{
    $logFile = 'fix_payments_error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo "Error: $message<br>";
}


function logSuccess($message)
{
    echo "Success: $message<br>";
}

echo "<h2>Fixing Payments Table Structure</h2>";


$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Payments'");
if (mysqli_num_rows($tableCheck) == 0) {
    logError("Payments table does not exist. Please run create_tables.php first.");
    exit;
}


$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM Payments LIKE 'PatientID'");
if (mysqli_num_rows($columnCheck) > 0) {
    logSuccess("PatientID column already exists in Payments table.");
} else {
    
    $alterQuery = "ALTER TABLE Payments ADD COLUMN PatientID INT AFTER BillID";
    if (mysqli_query($conn, $alterQuery)) {
        logSuccess("Added PatientID column to Payments table.");

        
        $fkQuery = "ALTER TABLE Payments ADD CONSTRAINT fk_patient_payment FOREIGN KEY (PatientID) REFERENCES Patients(PatientID)";
        if (mysqli_query($conn, $fkQuery)) {
            logSuccess("Added foreign key constraint for PatientID in Payments table.");
        } else {
            logError("Failed to add foreign key constraint: " . mysqli_error($conn));
        }

        
        $updateQuery = "UPDATE Payments p
                       JOIN Bills b ON p.BillID = b.BillID
                       SET p.PatientID = b.PatientID
                       WHERE p.PatientID IS NULL";

        if (mysqli_query($conn, $updateQuery)) {
            $affectedRows = mysqli_affected_rows($conn);
            logSuccess("Updated PatientID for $affectedRows payment records.");
        } else {
            logError("Failed to update PatientID values: " . mysqli_error($conn));
        }
    } else {
        logError("Failed to add PatientID column: " . mysqli_error($conn));
    }
}


echo "<h3>Final Payments Table Structure:</h3>";
$columnsResult = mysqli_query($conn, "SHOW COLUMNS FROM Payments");

echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($col = mysqli_fetch_assoc($columnsResult)) {
    echo "<tr>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . ($col['Default'] === NULL ? 'NULL' : $col['Default']) . "</td>";
    echo "<td>" . $col['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";


mysqli_close($conn);
?>