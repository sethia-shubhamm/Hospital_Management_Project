<?php

require_once '../../db_connect.php';


function logError($message)
{
    $logFile = 'error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


header('Content-Type: application/json');


$blood_group = isset($_GET['blood_group']) ? $_GET['blood_group'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';


$query = "SELECT * FROM BloodInventory";
$params = [];
$types = "";


if ($blood_group !== 'all') {
    $query .= " WHERE BloodType = ?";
    $params[] = $blood_group;
    $types .= "s";
}


try {
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $inventory = [];

        while ($row = mysqli_fetch_assoc($result)) {
            
            $quantity = (int) $row['Quantity'];
            $row_status = 'available';

            if ($quantity <= 2) {
                $row_status = 'critical';
            } else if ($quantity <= 5) {
                $row_status = 'low';
            }

            
            if ($status === 'all' || $status === $row_status) {
                
                $expiry_date = date('Y-m-d', strtotime($row['ExpiryDate']));

                
                $inventory[] = [
                    'BLOOD_TYPE' => $row['BloodType'],
                    'QUANTITY' => $quantity,
                    'EXPIRY_DATE' => $expiry_date,
                    'status' => $row_status
                ];
            }
        }

        mysqli_stmt_close($stmt);

        
        echo json_encode([
            'success' => true,
            'data' => $inventory
        ]);
    } else {
        
        logError("Failed to prepare statement: " . mysqli_error($conn));
        echo json_encode([
            'success' => false,
            'message' => "Failed to retrieve inventory data"
        ]);
    }
} catch (Exception $e) {
    logError("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "An error occurred: " . $e->getMessage()
    ]);
}


mysqli_close($conn);
?>