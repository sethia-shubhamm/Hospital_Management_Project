<?php
session_start();
require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = "../error_log.txt";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $response = ['success' => false, 'message' => ''];

    
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Patient') {
        $response['message'] = 'Please log in as a patient to book an appointment';
        echo json_encode($response);
        exit;
    }

    
    $patient_id = $_SESSION['user_id'];

    
    $doctor_id = isset($_POST['doctorId']) ? mysqli_real_escape_string($conn, $_POST['doctorId']) : null;
    $appointment_date = isset($_POST['appointmentDate']) ? mysqli_real_escape_string($conn, $_POST['appointmentDate']) : null;
    $appointment_time = isset($_POST['appointmentTime']) ? mysqli_real_escape_string($conn, $_POST['appointmentTime']) : null;
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : 'Regular Checkup';

    
    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
        $response['message'] = 'Please fill in all required fields';
        echo json_encode($response);
        exit;
    }

    
    $check_doctor = mysqli_query($conn, "SELECT * FROM Doctors WHERE DoctorID = '$doctor_id'");

    
    if (mysqli_num_rows($check_doctor) === 0 && isset($_POST['doctorName'])) {
        $doctor_name = mysqli_real_escape_string($conn, $_POST['doctorName']);

        
        $check_doctor = mysqli_query($conn, "SELECT * FROM Doctors WHERE DoctorName = '$doctor_name'");

        
        if (mysqli_num_rows($check_doctor) === 0) {
            $doctor_name_parts = explode(' ', $doctor_name);
            $first_name = isset($doctor_name_parts[1]) ? mysqli_real_escape_string($conn, $doctor_name_parts[1]) : '';
            $last_name = isset($doctor_name_parts[2]) ? mysqli_real_escape_string($conn, $doctor_name_parts[2]) : '';

            if (!empty($first_name) && !empty($last_name)) {
                $check_doctor = mysqli_query($conn, "SELECT * FROM Doctors WHERE FirstName = '$first_name' AND LastName = '$last_name'");
            }
        }

        
        if (mysqli_num_rows($check_doctor) > 0) {
            $doctor = mysqli_fetch_assoc($check_doctor);
            $doctor_id = $doctor['DoctorID'];
        } else {
            $response['message'] = 'Doctor not found in the system';
            echo json_encode($response);
            exit;
        }
    }

    
    $check_conflicts_query = "
        SELECT COUNT(*) AS conflict_count
        FROM Appointments
        WHERE (
            (PatientID = '$patient_id' AND AppointmentDate = '$appointment_date' AND AppointmentTime = '$appointment_time' AND Status NOT IN ('Cancelled', 'Completed'))
            OR 
            (DoctorID = '$doctor_id' AND AppointmentDate = '$appointment_date' AND AppointmentTime = '$appointment_time' AND Status NOT IN ('Cancelled', 'Completed'))
        )
    ";

    $conflict_result = mysqli_query($conn, $check_conflicts_query);
    $conflict_data = mysqli_fetch_assoc($conflict_result);

    if ($conflict_data['conflict_count'] > 0) {
        $response['message'] = 'Unable to book appointment: Either you already have an appointment at this time or the doctor is not available';
        echo json_encode($response);
        exit;
    }

    
    $procedure_exists = mysqli_query($conn, "SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name = 'book_appointment'");

    if (mysqli_num_rows($procedure_exists) > 0) {
        
        $call_procedure = "CALL book_appointment(?, ?, ?, ?, ?, @p_success, @p_message, @p_appointment_id)";
        $stmt = mysqli_prepare($conn, $call_procedure);

        if ($stmt) {
            
            if (strpos($appointment_time, ' ') !== false) {
                
                $time_parts = explode(' ', $appointment_time);
                $hour_minute = explode(':', $time_parts[0]);
                $hour = intval($hour_minute[0]);
                $minute = intval($hour_minute[1]);

                
                if ($time_parts[1] === 'PM' && $hour < 12) {
                    $hour += 12;
                }
                if ($time_parts[1] === 'AM' && $hour === 12) {
                    $hour = 0; 
                }

                $appointment_time = sprintf("%02d:%02d:00", $hour, $minute);
            }

            mysqli_stmt_bind_param($stmt, "iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason);

            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                
                $result = mysqli_query($conn, "SELECT @p_success AS success, @p_message AS message, @p_appointment_id AS appointment_id");
                $row = mysqli_fetch_assoc($result);

                if ($row['success']) {
                    $response['success'] = true;
                    $response['message'] = $row['message'];
                    $response['appointment_id'] = $row['appointment_id'];
                } else {
                    $response['message'] = $row['message'];
                }

                echo json_encode($response);
                exit;
            } else {
                $error = mysqli_error($conn);
                logError("Error executing stored procedure: " . $error);

                
                if (strpos($error, 'doctor is already booked') !== false || strpos($error, 'patient already has an appointment') !== false) {
                    $response['message'] = $error;
                } else {
                    $response['message'] = 'Error booking appointment. Please try again later.';
                }

                echo json_encode($response);
                exit;
            }
        }
    }

    
    

    
    $check_purpose = mysqli_query($conn, "SHOW COLUMNS FROM Appointments LIKE 'AppointmentPurpose'");
    $has_purpose = mysqli_num_rows($check_purpose) > 0;

    if (!$has_purpose) {
        $check_reason = mysqli_query($conn, "SHOW COLUMNS FROM Appointments LIKE 'Reason'");
        $has_reason = mysqli_num_rows($check_reason) > 0;
    }

    
    $check_status = mysqli_query($conn, "SHOW COLUMNS FROM Appointments LIKE 'Status'");
    $has_status = mysqli_num_rows($check_status) > 0;

    
    $insert_query = "INSERT INTO Appointments (PatientID, DoctorID, AppointmentDate, AppointmentTime";

    if ($has_purpose) {
        $insert_query .= ", AppointmentPurpose";
    } elseif ($has_reason) {
        $insert_query .= ", Reason";
    }

    if ($has_status) {
        $insert_query .= ", Status";
    }

    $insert_query .= ") VALUES ('$patient_id', '$doctor_id', '$appointment_date', '$appointment_time'";

    if ($has_purpose) {
        $insert_query .= ", '$reason'";
    } elseif ($has_reason) {
        $insert_query .= ", '$reason'";
    }

    if ($has_status) {
        $insert_query .= ", 'Scheduled'";
    }

    $insert_query .= ")";

    
    if (mysqli_query($conn, $insert_query)) {
        $appointment_id = mysqli_insert_id($conn);
        $response['success'] = true;
        $response['message'] = 'Appointment booked successfully!';
        $response['appointment_id'] = $appointment_id;
    } else {
        $error = mysqli_error($conn);
        $error_code = mysqli_errno($conn);

        logError("Error booking appointment: Code $error_code - $error");

        
        if (
            strpos($error, 'doctor is already booked') !== false ||
            strpos($error, 'patient already has an appointment') !== false
        ) {
            $response['message'] = $error;
        }
        
        else if ($error_code == 1062) {
            if (strpos($error, 'uc_doctor_datetime') !== false) {
                $response['message'] = 'This appointment time slot is already booked. Please select a different time.';
            } else {
                $response['message'] = 'Duplicate appointment detected: This appointment already exists.';
            }
        } else {
            $response['message'] = 'Error booking appointment: ' . $error;
        }
    }

    
    echo json_encode($response);
    exit;
} else {
    
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>