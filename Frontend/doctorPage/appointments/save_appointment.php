<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Doctor') {
    $_SESSION['login_error'] = "Please log in as a doctor to access this page";
    header("Location: ../../login/index.php");
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, '../error_log.txt');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : $_SESSION['user_id'];
    $patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : '';
    $appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
    $appointment_time = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
    $appointment_type = isset($_POST['appointment_type']) ? $_POST['appointment_type'] : '';
    $duration = isset($_POST['duration']) ? $_POST['duration'] : '30';
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';

    
    if (empty($patient_id) || empty($appointment_date) || empty($appointment_time)) {
        $_SESSION['error_message'] = "All required fields must be filled out";
        header("Location: index.php");
        exit();
    }

    
    $status = 'Scheduled';

    
    $table_check_query = "SHOW TABLES LIKE 'Appointments'";
    $table_result = mysqli_query($conn, $table_check_query);

    if (!$table_result) {
        logError("Database error checking tables: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Database error occurred, please try again";
        header("Location: index.php");
        exit();
    }

    
    if (mysqli_num_rows($table_result) == 0) {
        
        $create_table_query = "CREATE TABLE Appointments (
            AppointmentID INT AUTO_INCREMENT PRIMARY KEY,
            PatientID INT NOT NULL,
            DoctorID INT NOT NULL,
            AppointmentDate DATE NOT NULL,
            AppointmentTime TIME NOT NULL,
            AppointmentPurpose VARCHAR(255),
            Status VARCHAR(50) DEFAULT 'Scheduled',
            Duration INT DEFAULT 30,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $create_result = mysqli_query($conn, $create_table_query);

        if (!$create_result) {
            logError("Failed to create Appointments table: " . mysqli_error($conn));
            $_SESSION['error_message'] = "Failed to set up appointment system, please contact administrator";
            header("Location: index.php");
            exit();
        }
    }

    
    $column_check_query = "SHOW COLUMNS FROM Appointments";
    $column_result = mysqli_query($conn, $column_check_query);

    if (!$column_result) {
        logError("Database error checking columns: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Database error occurred, please try again";
        header("Location: index.php");
        exit();
    }

    
    $doctorIdColumn = 'DoctorID';
    $patientIdColumn = 'PatientID';
    $appointmentDateColumn = 'AppointmentDate';
    $appointmentTimeColumn = 'AppointmentTime';
    $appointmentPurposeColumn = 'AppointmentPurpose';
    $statusColumn = 'Status';
    $durationColumn = 'Duration';

    
    $columns = [];
    while ($column = mysqli_fetch_assoc($column_result)) {
        $columns[] = strtolower($column['Field']);
        $column_name = $column['Field']; 

        
        if (strtolower($column_name) === 'doctorid' || strtolower($column_name) === 'doctor_id') {
            $doctorIdColumn = $column_name;
        }
        if (strtolower($column_name) === 'patientid' || strtolower($column_name) === 'patient_id') {
            $patientIdColumn = $column_name;
        }
        if (strtolower($column_name) === 'appointmentdate' || strtolower($column_name) === 'appointment_date') {
            $appointmentDateColumn = $column_name;
        }
        if (strtolower($column_name) === 'appointmenttime' || strtolower($column_name) === 'appointment_time') {
            $appointmentTimeColumn = $column_name;
        }
        if (strtolower($column_name) === 'appointmentpurpose' || strtolower($column_name) === 'purpose') {
            $appointmentPurposeColumn = $column_name;
        }
        if (strtolower($column_name) === 'status') {
            $statusColumn = $column_name;
        }
        if (strtolower($column_name) === 'duration') {
            $durationColumn = $column_name;
        }
    }

    
    $purpose_column_exists = false;
    foreach ($columns as $col) {
        if (in_array($col, ['appointmentpurpose', 'purpose'])) {
            $purpose_column_exists = true;
            break;
        }
    }

    
    $status_column_exists = false;
    foreach ($columns as $col) {
        if ($col === 'status') {
            $status_column_exists = true;
            break;
        }
    }

    
    $duration_column_exists = false;
    foreach ($columns as $col) {
        if ($col === 'duration') {
            $duration_column_exists = true;
            break;
        }
    }

    
    if (!$purpose_column_exists) {
        
        $add_purpose_column = "ALTER TABLE Appointments ADD COLUMN AppointmentPurpose VARCHAR(255)";
        $add_result = mysqli_query($conn, $add_purpose_column);

        if (!$add_result) {
            logError("Failed to add AppointmentPurpose column: " . mysqli_error($conn));
            
            
            $appointmentPurposeColumn = 'AppointmentPurpose';
        } else {
            $purpose_column_exists = true;
        }
    }

    
    $check_duplicate_query = "SELECT * FROM Appointments 
                             WHERE $doctorIdColumn = '$doctor_id' 
                             AND $appointmentDateColumn = '$appointment_date' 
                             AND $appointmentTimeColumn = '$appointment_time'";

    
    logError("Running duplicate check: " . $check_duplicate_query);

    $check_result = mysqli_query($conn, $check_duplicate_query);

    if (!$check_result) {
        logError("Database error checking for duplicates: " . mysqli_error($conn));
    } else if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error_message'] = "You already have an appointment scheduled at this time";
        header("Location: index.php");
        exit();
    }

    
    $insert_query = "INSERT INTO Appointments (
        $doctorIdColumn, 
        $patientIdColumn, 
        $appointmentDateColumn, 
        $appointmentTimeColumn";

    
    if ($purpose_column_exists) {
        $insert_query .= ", 
        $appointmentPurposeColumn";
    }

    
    if ($status_column_exists) {
        $insert_query .= ", 
        $statusColumn";
    }

    
    if ($duration_column_exists) {
        $insert_query .= ", 
        $durationColumn";
    }

    $insert_query .= ") VALUES (
        '$doctor_id',
        '$patient_id',
        '$appointment_date',
        '$appointment_time'";

    
    if ($purpose_column_exists) {
        $insert_query .= ",
        '$reason'";
    }

    
    if ($status_column_exists) {
        $insert_query .= ",
        '$status'";
    }

    
    if ($duration_column_exists) {
        $insert_query .= ",
        '$duration'";
    }

    $insert_query .= ")";

    
    logError("Running insert query: " . $insert_query);

    $insert_result = mysqli_query($conn, $insert_query);

    if (!$insert_result) {
        logError("Database error creating appointment: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Failed to schedule appointment: " . mysqli_error($conn);
        header("Location: index.php");
        exit();
    }

    
    $_SESSION['success_message'] = "Appointment scheduled successfully";
    header("Location: index.php");
    exit();
} else {
    
    header("Location: index.php");
    exit();
}
?>