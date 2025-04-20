<?php



require_once 'db_connect.php';


function logError($message)
{
    $logFile = 'fix_payments_amount_error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo "Error: $message<br>";
}


function logSuccess($message)
{
    echo "Success: $message<br>";
}

echo "<h2>Fixing Payments Table Amount Columns</h2>";


$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Payments'");
if (mysqli_num_rows($tableCheck) == 0) {
    logError("Payments table does not exist. Please run create_tables.php first.");
    exit;
}


$checkAmountCol = mysqli_query($conn, "SHOW COLUMNS FROM Payments LIKE 'Amount'");
$hasAmountCol = mysqli_num_rows($checkAmountCol) > 0;


$checkAmountPaidCol = mysqli_query($conn, "SHOW COLUMNS FROM Payments LIKE 'AmountPaid'");
$hasAmountPaidCol = mysqli_num_rows($checkAmountPaidCol) > 0;


if ($hasAmountCol && $hasAmountPaidCol) {
    logSuccess("Both 'Amount' and 'AmountPaid' columns exist. Syncing values...");

    
    $syncFromAmount = "UPDATE Payments SET AmountPaid = Amount WHERE Amount IS NOT NULL AND AmountPaid IS NULL";
    if (mysqli_query($conn, $syncFromAmount)) {
        $rowsUpdated = mysqli_affected_rows($conn);
        logSuccess("Updated $rowsUpdated records with AmountPaid = Amount");
    } else {
        logError("Failed to sync AmountPaid from Amount: " . mysqli_error($conn));
    }

    
    $syncFromAmountPaid = "UPDATE Payments SET Amount = AmountPaid WHERE AmountPaid IS NOT NULL AND Amount IS NULL";
    if (mysqli_query($conn, $syncFromAmountPaid)) {
        $rowsUpdated = mysqli_affected_rows($conn);
        logSuccess("Updated $rowsUpdated records with Amount = AmountPaid");
    } else {
        logError("Failed to sync Amount from AmountPaid: " . mysqli_error($conn));
    }
}

else if ($hasAmountPaidCol && !$hasAmountCol) {
    logSuccess("Only 'AmountPaid' column exists. Adding 'Amount' column...");

    
    $addAmount = "ALTER TABLE Payments ADD COLUMN Amount DECIMAL(10, 2) AFTER PatientID";
    if (mysqli_query($conn, $addAmount)) {
        logSuccess("Added 'Amount' column to Payments table");

        
        $copyValues = "UPDATE Payments SET Amount = AmountPaid WHERE AmountPaid IS NOT NULL";
        if (mysqli_query($conn, $copyValues)) {
            $rowsUpdated = mysqli_affected_rows($conn);
            logSuccess("Copied $rowsUpdated values from AmountPaid to Amount");
        } else {
            logError("Failed to copy values to Amount: " . mysqli_error($conn));
        }
    } else {
        logError("Failed to add Amount column: " . mysqli_error($conn));
    }
}

else if ($hasAmountCol && !$hasAmountPaidCol) {
    logSuccess("Only 'Amount' column exists. Adding 'AmountPaid' column...");

    
    $addAmountPaid = "ALTER TABLE Payments ADD COLUMN AmountPaid DECIMAL(10, 2) AFTER PatientID";
    if (mysqli_query($conn, $addAmountPaid)) {
        logSuccess("Added 'AmountPaid' column to Payments table");

        
        $copyValues = "UPDATE Payments SET AmountPaid = Amount WHERE Amount IS NOT NULL";
        if (mysqli_query($conn, $copyValues)) {
            $rowsUpdated = mysqli_affected_rows($conn);
            logSuccess("Copied $rowsUpdated values from Amount to AmountPaid");
        } else {
            logError("Failed to copy values to AmountPaid: " . mysqli_error($conn));
        }
    } else {
        logError("Failed to add AmountPaid column: " . mysqli_error($conn));
    }
}

else {
    logError("Neither 'Amount' nor 'AmountPaid' column exists in Payments table. This should not happen as one of them should be defined in the table structure.");
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