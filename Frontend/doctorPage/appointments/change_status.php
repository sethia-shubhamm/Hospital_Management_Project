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


if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['status']) && !empty($_GET['status'])) {
    $appointment_id = $_GET['id'];
    $new_status = $_GET['status'];
    $doctor_id = $_SESSION['user_id'];

    
    $allowed_statuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no-show'];
    if (!in_array(strtolower($new_status), $allowed_statuses)) {
        $_SESSION['error_message'] = "Invalid status value";
        header("Location: index.php");
        exit();
    }

    
    $column_check_query = "SHOW COLUMNS FROM Appointments";
    $column_result = mysqli_query($conn, $column_check_query);

    if (!$column_result) {
        logError("Database error checking columns: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Database error occurred, please try again";
        header("Location: index.php");
        exit();
    }

    
    $appointmentIdColumn = 'AppointmentID';
    $doctorIdColumn = 'DoctorID';
    $statusColumn = 'Status';
    $has_status_column = false;

    
    $columns = [];
    while ($column = mysqli_fetch_assoc($column_result)) {
        $columns[] = strtolower($column['Field']);
    }

    if (in_array('appointment_id', $columns)) {
        $appointmentIdColumn = 'appointment_id';
    } else if (in_array('appointmentid', $columns)) {
        $appointmentIdColumn = 'appointmentid';
    }

    if (in_array('doctor_id', $columns)) {
        $doctorIdColumn = 'doctor_id';
    }

    
    if (in_array('status', $columns)) {
        $statusColumn = 'status';
        $has_status_column = true;
    } else if (in_array('Status', $columns)) {
        $statusColumn = 'Status';
        $has_status_column = true;
    }

    
    $check_query = "SELECT * FROM Appointments WHERE $appointmentIdColumn = $appointment_id AND $doctorIdColumn = '$doctor_id'";
    $check_result = mysqli_query($conn, $check_query);

    if (!$check_result) {
        logError("Database error checking appointment: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Database error occurred, please try again";
        header("Location: index.php");
        exit();
    }

    if (mysqli_num_rows($check_result) == 0) {
        $_SESSION['error_message'] = "You are not authorized to update this appointment";
        header("Location: index.php");
        exit();
    }

    if ($has_status_column) {
        
        $update_query = "UPDATE Appointments SET $statusColumn = '$new_status' WHERE $appointmentIdColumn = $appointment_id AND $doctorIdColumn = '$doctor_id'";
        $update_result = mysqli_query($conn, $update_query);

        if (!$update_result) {
            logError("Database error updating appointment status: " . mysqli_error($conn));
            $_SESSION['error_message'] = "Failed to update appointment status: " . mysqli_error($conn);
            header("Location: index.php");
            exit();
        }

        
        $_SESSION['success_message'] = "Appointment status updated to $new_status";
    } else {
        
        if (strtolower($new_status) == 'completed') {
            
            $has_notes_column = in_array('notes', $columns) || in_array('Notes', $columns);
            $notesColumn = in_array('notes', $columns) ? 'notes' : 'Notes';

            if ($has_notes_column) {
                $update_query = "UPDATE Appointments SET $notesColumn = CONCAT(IFNULL($notesColumn, ''), ' [Completed on " . date('Y-m-d H:i:s') . "]') WHERE $appointmentIdColumn = $appointment_id AND $doctorIdColumn = '$doctor_id'";
                $update_result = mysqli_query($conn, $update_query);

                if (!$update_result) {
                    logError("Database error marking appointment as completed: " . mysqli_error($conn));
                    $_SESSION['error_message'] = "Failed to mark appointment as completed: " . mysqli_error($conn);
                    header("Location: index.php");
                    exit();
                }

                $_SESSION['success_message'] = "Appointment marked as completed";
            } else {
                
                if (strtolower($new_status) == 'cancelled') {
                    $delete_query = "DELETE FROM Appointments WHERE $appointmentIdColumn = $appointment_id AND $doctorIdColumn = '$doctor_id'";
                    $delete_result = mysqli_query($conn, $delete_query);

                    if (!$delete_result) {
                        logError("Database error deleting appointment: " . mysqli_error($conn));
                        $_SESSION['error_message'] = "Failed to delete appointment: " . mysqli_error($conn);
                        header("Location: index.php");
                        exit();
                    }

                    $_SESSION['success_message'] = "Appointment has been deleted";
                } else {
                    $_SESSION['warning_message'] = "Status column doesn't exist. The appointment has been processed.";
                }
            }
        }
    }

    header("Location: index.php");
    exit();
} else {
    
    $_SESSION['error_message'] = "Missing appointment ID or status";
    header("Location: index.php");
    exit();
}
?>