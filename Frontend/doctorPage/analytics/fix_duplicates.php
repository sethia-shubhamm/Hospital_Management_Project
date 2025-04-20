<?php
require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = "../error_log.txt";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


function logAction($message)
{
    $logFile = "../fix_duplicates_log.txt";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

echo "<h1>Duplicate Appointment Fixer</h1>";
echo "<p>This script will find and fix duplicate appointments in the database.</p>";



$check_status = mysqli_query($conn, "SHOW COLUMNS FROM Appointments LIKE 'Status'");
$has_status = mysqli_num_rows($check_status) > 0;

$find_duplicates = "
    SELECT DoctorID, AppointmentDate, AppointmentTime, COUNT(*) as count, 
           GROUP_CONCAT(AppointmentID) as ids
    FROM Appointments 
    WHERE 1=1";


if ($has_status) {
    $find_duplicates .= " AND Status NOT IN ('Cancelled', 'Completed')";
}

$find_duplicates .= "
    GROUP BY DoctorID, AppointmentDate, AppointmentTime
    HAVING COUNT(*) > 1
";

$duplicates_result = mysqli_query($conn, $find_duplicates);

if (!$duplicates_result) {
    echo "<div style='color: red;'>Error finding duplicates: " . mysqli_error($conn) . "</div>";
    logError("Error finding duplicates: " . mysqli_error($conn));
    exit;
}

if (mysqli_num_rows($duplicates_result) == 0) {
    echo "<div style='color: green;'>No duplicate appointments found.</div>";
} else {
    echo "<div style='color: red;'>Found " . mysqli_num_rows($duplicates_result) . " sets of duplicate appointments. Processing...</div>";

    $duplicates_fixed = 0;

    
    while ($row = mysqli_fetch_assoc($duplicates_result)) {
        $doctor_id = $row['DoctorID'];
        $date = $row['AppointmentDate'];
        $time = $row['AppointmentTime'];
        $ids = explode(',', $row['ids']);

        
        $keep_id = min($ids);
        $delete_ids = array_filter($ids, function ($id) use ($keep_id) {
            return $id != $keep_id;
        });

        if (count($delete_ids) > 0) {
            $delete_ids_str = implode(',', $delete_ids);
            $delete_query = "DELETE FROM Appointments WHERE AppointmentID IN ($delete_ids_str)";

            if (mysqli_query($conn, $delete_query)) {
                $affected = mysqli_affected_rows($conn);
                $duplicates_fixed += $affected;
                echo "<div>Deleted $affected duplicate appointments for Doctor $doctor_id on $date at $time</div>";
                logAction("Deleted appointment IDs: $delete_ids_str, kept ID: $keep_id");
            } else {
                echo "<div style='color: red;'>Error deleting duplicates: " . mysqli_error($conn) . "</div>";
                logError("Error deleting duplicates: " . mysqli_error($conn));
            }
        }
    }

    echo "<div style='color: green;'>Fixed $duplicates_fixed duplicate appointments.</div>";
}


echo "<h2>Adding database constraints to prevent future duplicates</h2>";


$add_constraint = "
    ALTER TABLE Appointments 
    ADD CONSTRAINT uc_doctor_datetime 
    UNIQUE (DoctorID, AppointmentDate, AppointmentTime)
";

if (mysqli_query($conn, $add_constraint)) {
    echo "<div style='color: green;'>Successfully added unique constraint on (DoctorID, AppointmentDate, AppointmentTime)</div>";
    logAction("Added unique constraint successfully");
} else {
    echo "<div style='color: orange;'>Could not add constraint directly: " . mysqli_error($conn) . "</div>";
    logError("Failed to add constraint: " . mysqli_error($conn));

    
    $add_index = "
        CREATE UNIQUE INDEX uc_doctor_datetime 
        ON Appointments (DoctorID, AppointmentDate, AppointmentTime)
    ";

    if (mysqli_query($conn, $add_index)) {
        echo "<div style='color: green;'>Successfully added unique index on (DoctorID, AppointmentDate, AppointmentTime)</div>";
        logAction("Added unique index successfully");
    } else {
        echo "<div style='color: red;'>Failed to add constraint or index: " . mysqli_error($conn) . "</div>";
        logError("Failed to add index: " . mysqli_error($conn));
    }
}


$check_trigger = "SHOW TRIGGERS LIKE 'prevent_double_booking'";
$trigger_result = mysqli_query($conn, $check_trigger);

if (mysqli_num_rows($trigger_result) > 0) {
    echo "<div style='color: blue;'>Double booking prevention trigger already exists. Recreating for latest version...</div>";
    mysqli_query($conn, "DROP TRIGGER IF EXISTS prevent_double_booking");
}



$create_trigger = "
CREATE TRIGGER prevent_double_booking
BEFORE INSERT ON Appointments
FOR EACH ROW
BEGIN
    DECLARE appointment_count INT;
    
    -- Check if doctor is already booked at this time
    SELECT COUNT(*) INTO appointment_count
    FROM Appointments
    WHERE DoctorID = NEW.DoctorID
    AND AppointmentDate = NEW.AppointmentDate
    AND AppointmentTime = NEW.AppointmentTime";


if ($has_status) {
    $create_trigger .= "
    AND Status NOT IN ('Cancelled', 'Completed')";
}

$create_trigger .= ";
    
    IF appointment_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This doctor is already booked at the specified time';
    END IF;
    
    -- Check if patient already has an appointment at this time
    SELECT COUNT(*) INTO appointment_count
    FROM Appointments
    WHERE PatientID = NEW.PatientID
    AND AppointmentDate = NEW.AppointmentDate
    AND AppointmentTime = NEW.AppointmentTime";


if ($has_status) {
    $create_trigger .= "
    AND Status NOT IN ('Cancelled', 'Completed')";
}

$create_trigger .= ";
    
    IF appointment_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This patient already has an appointment at this time';
    END IF;
END;
";

if (mysqli_query($conn, $create_trigger)) {
    echo "<div style='color: green;'>Successfully created/updated double booking prevention trigger</div>";
    logAction("Created/updated double booking trigger");
} else {
    echo "<div style='color: red;'>Failed to create trigger: " . mysqli_error($conn) . "</div>";
    logError("Failed to create trigger: " . mysqli_error($conn));
}

echo "<hr>";
echo "<p>Process completed. Please check the logs for details.</p>";
echo "<p><a href='book_appointment_demo.php'>Return to Appointment Booking Demo</a></p>";
?>