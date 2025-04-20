<?php



require_once 'db_connect.php';


function logActivity($message)
{
    echo "<p>$message</p>";
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'db_update.log');
}


$tableCheck = "SHOW TABLES LIKE 'Appointments'";
$tableResult = mysqli_query($conn, $tableCheck);

if (mysqli_num_rows($tableResult) == 0) {
    logActivity("Error: Appointments table not found!");
    exit;
}


$columnCheck = "SHOW COLUMNS FROM Appointments LIKE 'Status'";
$columnResult = mysqli_query($conn, $columnCheck);

if (mysqli_num_rows($columnResult) > 0) {
    logActivity("Status column already exists in Appointments table.");
} else {
    
    $addColumn = "ALTER TABLE Appointments ADD COLUMN Status VARCHAR(20) DEFAULT 'Scheduled'";

    if (mysqli_query($conn, $addColumn)) {
        logActivity("Successfully added Status column to Appointments table!");

        
        $updateStatus = "UPDATE Appointments SET Status = 'Scheduled' WHERE Status IS NULL";
        if (mysqli_query($conn, $updateStatus)) {
            logActivity("Updated all existing appointments with default status 'Scheduled'");
        } else {
            logActivity("Error updating existing appointments: " . mysqli_error($conn));
        }
    } else {
        logActivity("Error adding Status column: " . mysqli_error($conn));
    }
}


echo "<h2>Current Appointments Table Structure:</h2>";
$structureQuery = "DESCRIBE Appointments";
$structureResult = mysqli_query($conn, $structureQuery);

echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = mysqli_fetch_assoc($structureResult)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p>Hospital Management System - Appointment System Update</p>";


mysqli_close($conn);
?>