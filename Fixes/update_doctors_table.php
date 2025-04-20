<?php

require_once 'db_connect.php';

echo "<h1>Updating Doctors Table Structure</h1>";


function columnExists($conn, $table, $column)
{
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'");
    return mysqli_num_rows($result) > 0;
}


$tableExists = mysqli_query($conn, "SHOW TABLES LIKE 'Doctors'");
if (mysqli_num_rows($tableExists) == 0) {
    echo "<p>Error: Doctors table does not exist!</p>";
    exit;
}


$columnsQuery = "SHOW COLUMNS FROM Doctors";
$columnsResult = mysqli_query($conn, $columnsQuery);

$existingColumns = [];
while ($column = mysqli_fetch_assoc($columnsResult)) {
    $existingColumns[] = $column['Field'];
}

echo "<p>Existing columns in Doctors table: " . implode(", ", $existingColumns) . "</p>";


$requiredColumns = [
    'Email' => 'VARCHAR(100)',
    'Phone' => 'VARCHAR(20)',
    'Qualification' => 'VARCHAR(100)',
    'JoinDate' => 'DATE',
    'Status' => 'VARCHAR(20) DEFAULT \'Active\''
];


$columnsAdded = 0;
foreach ($requiredColumns as $column => $definition) {
    if (!in_array($column, $existingColumns)) {
        $alterQuery = "ALTER TABLE Doctors ADD COLUMN $column $definition";
        if (mysqli_query($conn, $alterQuery)) {
            echo "<p>Added column '$column' with definition: $definition</p>";
            $columnsAdded++;
        } else {
            echo "<p>Error adding column '$column': " . mysqli_error($conn) . "</p>";
        }
    }
}

if ($columnsAdded == 0) {
    echo "<p>All required columns already exist in the Doctors table.</p>";
} else {
    echo "<p>Added $columnsAdded new columns to the Doctors table.</p>";
}


echo "<h2>Updated Doctors Table Structure:</h2>";
$finalColumns = mysqli_query($conn, "SHOW COLUMNS FROM Doctors");

echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($col = mysqli_fetch_assoc($finalColumns)) {
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