<?php



require_once 'db_connect.php';


function logMessage($message)
{
    echo "$message<br>";
}

echo "<h2>Checking Payments Table Structure</h2>";


$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Payments'");
if (mysqli_num_rows($tableCheck) == 0) {
    logMessage("Error: Payments table does not exist. Please run create_tables.php first.");
    exit;
}


$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM Payments LIKE 'PaymentMethod'");
if (mysqli_num_rows($columnCheck) > 0) {
    logMessage("PaymentMethod column already exists in Payments table.");
} else {
    
    $alterQuery = "ALTER TABLE Payments ADD COLUMN PaymentMethod VARCHAR(50) DEFAULT 'Credit Card'";
    if (mysqli_query($conn, $alterQuery)) {
        logMessage("Added PaymentMethod column to Payments table.");
    } else {
        logMessage("Error: Failed to add PaymentMethod column: " . mysqli_error($conn));
    }
}


echo "<h3>Current Payments Table Structure:</h3>";
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