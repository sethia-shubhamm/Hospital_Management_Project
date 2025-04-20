<?php
require_once 'db_connect.php';


$bloodTypes = array('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-');


mysqli_begin_transaction($conn);

try {
    
    $clearQuery = "DELETE FROM BloodInventory";
    mysqli_query($conn, $clearQuery);

    
    foreach ($bloodTypes as $type) {
        $quantity = rand(3, 20); 
        $expiryDate = date('Y-m-d', strtotime('+' . rand(10, 30) . ' days')); 

        $query = "INSERT INTO BloodInventory (BloodType, Quantity, ExpiryDate) 
                  VALUES ('$type', $quantity, '$expiryDate')";

        if (!mysqli_query($conn, $query)) {
            throw new Exception("Error adding blood type $type: " . mysqli_error($conn));
        }

        echo "✅ Added $type with quantity $quantity (Expiry: $expiryDate)<br>";
    }

    mysqli_commit($conn);
    echo "<h3>Blood inventory successfully initialized!</h3>";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "❌ Error: " . $e->getMessage();
}

mysqli_close($conn);
?>