<?php
require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


$create_efficiency_function = "
CREATE FUNCTION IF NOT EXISTS calculate_doctor_efficiency(
    doctor_id INT
) 
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE total_appointments INT DEFAULT 0;
    DECLARE completed_appointments INT DEFAULT 0;
    DECLARE cancelled_appointments INT DEFAULT 0;
    DECLARE efficiency_score DECIMAL(10,2) DEFAULT 0.0;
    
    -- Get total appointments
    SELECT COUNT(*) INTO total_appointments 
    FROM Appointments
    WHERE DoctorID = doctor_id
    AND AppointmentDate BETWEEN DATE_SUB(CURDATE(), INTERVAL 3 MONTH) AND CURDATE();
    
    -- Get completed appointments
    SELECT COUNT(*) INTO completed_appointments 
    FROM Appointments
    WHERE DoctorID = doctor_id
    AND Status = 'Completed'
    AND AppointmentDate BETWEEN DATE_SUB(CURDATE(), INTERVAL 3 MONTH) AND CURDATE();
    
    -- Get cancelled appointments
    SELECT COUNT(*) INTO cancelled_appointments 
    FROM Appointments
    WHERE DoctorID = doctor_id
    AND Status = 'Cancelled'
    AND AppointmentDate BETWEEN DATE_SUB(CURDATE(), INTERVAL 3 MONTH) AND CURDATE();
    
    -- Calculate efficiency score (completed/total * 100) - (cancelled penalty)
    IF total_appointments > 0 THEN
        SET efficiency_score = (completed_appointments / total_appointments * 100) - (cancelled_appointments / total_appointments * 10);
    END IF;
    
    -- Ensure score is between 0 and 100
    IF efficiency_score < 0 THEN
        SET efficiency_score = 0;
    ELSEIF efficiency_score > 100 THEN
        SET efficiency_score = 100;
    END IF;
    
    RETURN efficiency_score;
END;
";

if (!mysqli_query($conn, $create_efficiency_function)) {
    logError("Failed to create efficiency function: " . mysqli_error($conn));
}


$check_trigger = "SHOW TRIGGERS LIKE 'appointment_status_history'";
$trigger_result = mysqli_query($conn, $check_trigger);

if (mysqli_num_rows($trigger_result) == 0) {
    
    $create_history_table = "
    CREATE TABLE IF NOT EXISTS AppointmentStatusHistory (
        HistoryID INT AUTO_INCREMENT PRIMARY KEY,
        AppointmentID INT NOT NULL,
        OldStatus VARCHAR(20),
        NewStatus VARCHAR(20) NOT NULL,
        ChangedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ChangedBy VARCHAR(50),
        FOREIGN KEY (AppointmentID) REFERENCES Appointments(AppointmentID) ON DELETE CASCADE
    )";

    if (!mysqli_query($conn, $create_history_table)) {
        logError("Failed to create history table: " . mysqli_error($conn));
    }

    
    $create_trigger = "
    CREATE TRIGGER appointment_status_history
    AFTER UPDATE ON Appointments
    FOR EACH ROW
    BEGIN
        IF OLD.Status <> NEW.Status THEN
            INSERT INTO AppointmentStatusHistory (AppointmentID, OldStatus, NewStatus, ChangedBy)
            VALUES (OLD.AppointmentID, OLD.Status, NEW.Status, CURRENT_USER());
        END IF;
    END;
    ";

    if (!mysqli_query($conn, $create_trigger)) {
        logError("Failed to create trigger: " . mysqli_error($conn));
    }
}


$check_procedure = "SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name = 'generate_patient_report'";
$procedure_result = mysqli_query($conn, $check_procedure);

if (mysqli_num_rows($procedure_result) == 0) {
    $create_patient_report_procedure = "
    CREATE PROCEDURE generate_patient_report(
        IN doctor_id INT,
        IN start_date DATE,
        IN end_date DATE
    )
    BEGIN
        DECLARE done INT DEFAULT FALSE;
        DECLARE patient_id INT;
        DECLARE patient_name VARCHAR(100);
        DECLARE visit_count INT;
        DECLARE last_visit_date DATE;
        
        -- Declare cursor for patients
        DECLARE patient_cursor CURSOR FOR 
            SELECT 
                p.PatientID,
                CONCAT(p.FirstName, ' ', p.LastName) AS PatientName,
                COUNT(a.AppointmentID) AS VisitCount,
                MAX(a.AppointmentDate) AS LastVisitDate
            FROM Patients p
            JOIN Appointments a ON p.PatientID = a.PatientID
            WHERE a.DoctorID = doctor_id
            AND a.AppointmentDate BETWEEN start_date AND end_date
            GROUP BY p.PatientID, PatientName
            ORDER BY VisitCount DESC;
            
        -- Declare handler for no more rows
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
        
        -- Create temporary table to store results
        DROP TEMPORARY TABLE IF EXISTS PatientReport;
        CREATE TEMPORARY TABLE PatientReport (
            PatientID INT,
            PatientName VARCHAR(100),
            VisitCount INT,
            LastVisitDate DATE,
            CommonConditions TEXT,
            TreatmentSummary TEXT
        );
        
        -- Open cursor
        OPEN patient_cursor;
        
        -- Loop through patients
        patient_loop: LOOP
            FETCH patient_cursor INTO patient_id, patient_name, visit_count, last_visit_date;
            
            IF done THEN
                LEAVE patient_loop;
            END IF;
            
            -- Get common conditions for this patient
            SET @conditions = (
                SELECT GROUP_CONCAT(DISTINCT Reason SEPARATOR ', ')
                FROM Appointments
                WHERE PatientID = patient_id
                AND DoctorID = doctor_id
                AND AppointmentDate BETWEEN start_date AND end_date
                GROUP BY PatientID
            );
            
            -- Get treatment summary using a simple logic
            SET @treatment_summary = CASE
                WHEN visit_count > 5 THEN 'Frequent patient, needs comprehensive care plan'
                WHEN visit_count BETWEEN 3 AND 5 THEN 'Regular follow-up required'
                ELSE 'Occasional visits, standard care'
            END;
            
            -- Insert into temporary table
            INSERT INTO PatientReport (PatientID, PatientName, VisitCount, LastVisitDate, CommonConditions, TreatmentSummary)
            VALUES (patient_id, patient_name, visit_count, last_visit_date, @conditions, @treatment_summary);
        END LOOP;
        
        -- Close cursor
        CLOSE patient_cursor;
        
        -- Return the result
        SELECT * FROM PatientReport;
        
        -- Calculate additional statistics
        SELECT 
            COUNT(DISTINCT PatientID) AS TotalPatients,
            AVG(VisitCount) AS AverageVisitsPerPatient,
            MAX(VisitCount) AS MaxVisits,
            MIN(VisitCount) AS MinVisits
        FROM PatientReport;
        
        -- Get condition statistics
        SELECT 
            Reason AS Condition,
            COUNT(*) AS Occurrences,
            COUNT(DISTINCT PatientID) AS UniquePatients,
            (COUNT(*) / (SELECT COUNT(*) FROM Appointments WHERE DoctorID = doctor_id AND AppointmentDate BETWEEN start_date AND end_date)) * 100 AS PercentageOfTotal
        FROM Appointments
        WHERE DoctorID = doctor_id
        AND AppointmentDate BETWEEN start_date AND end_date
        GROUP BY Reason
        ORDER BY Occurrences DESC;
    END;
    ";

    if (!mysqli_query($conn, $create_patient_report_procedure)) {
        logError("Failed to create patient report procedure: " . mysqli_error($conn));
    }
}


$create_analytics_view = "
CREATE OR REPLACE VIEW DoctorAnalyticsView AS
SELECT 
    d.DoctorID,
    CONCAT(d.FirstName, ' ', d.LastName) AS DoctorName,
    d.Specialization,
    COUNT(a.AppointmentID) AS TotalAppointments,
    SUM(CASE WHEN a.Status = 'Completed' THEN 1 ELSE 0 END) AS CompletedAppointments,
    SUM(CASE WHEN a.Status = 'Cancelled' THEN 1 ELSE 0 END) AS CancelledAppointments,
    SUM(CASE WHEN a.Status = 'Scheduled' THEN 1 ELSE 0 END) AS ScheduledAppointments,
    COUNT(DISTINCT a.PatientID) AS UniquePatients,
    AVG(DATEDIFF(a.AppointmentDate, a.CreatedAt)) AS AvgBookingLeadTimeDays,
    calculate_doctor_efficiency(d.DoctorID) AS EfficiencyScore
FROM Doctors d
LEFT JOIN Appointments a ON d.DoctorID = a.DoctorID
GROUP BY d.DoctorID, DoctorName, d.Specialization
";

if (!mysqli_query($conn, $create_analytics_view)) {
    logError("Failed to create analytics view: " . mysqli_error($conn));
}


$check_double_booking_trigger = "SHOW TRIGGERS LIKE 'prevent_double_booking'";
$double_booking_result = mysqli_query($conn, $check_double_booking_trigger);


$check_status = mysqli_query($conn, "SHOW COLUMNS FROM Appointments LIKE 'Status'");
$has_status = mysqli_num_rows($check_status) > 0;


$add_unique_constraint = "
ALTER TABLE Appointments 
ADD CONSTRAINT IF NOT EXISTS uc_doctor_datetime 
UNIQUE (DoctorID, AppointmentDate, AppointmentTime);
";

if (!mysqli_query($conn, $add_unique_constraint)) {
    logError("Failed to add unique constraint on appointments: " . mysqli_error($conn));
    
    $alt_constraint = "
    CREATE UNIQUE INDEX IF NOT EXISTS uc_doctor_datetime 
    ON Appointments (DoctorID, AppointmentDate, AppointmentTime);
    ";
    if (!mysqli_query($conn, $alt_constraint)) {
        logError("Also failed with alternative constraint syntax: " . mysqli_error($conn));
    }
}


if (mysqli_num_rows($double_booking_result) > 0) {
    
    mysqli_query($conn, "DROP TRIGGER IF EXISTS prevent_double_booking");
}

$create_double_booking_trigger = "
CREATE TRIGGER prevent_double_booking
BEFORE INSERT ON Appointments
FOR EACH ROW
BEGIN
    DECLARE appointment_count INT;
    
    -- Check if doctor is already booked at this time and not cancelled/completed
    SELECT COUNT(*) INTO appointment_count
    FROM Appointments
    WHERE DoctorID = NEW.DoctorID
    AND AppointmentDate = NEW.AppointmentDate
    AND AppointmentTime = NEW.AppointmentTime";


if ($has_status) {
    $create_double_booking_trigger .= "
    AND Status NOT IN ('Cancelled', 'Completed')";
}

$create_double_booking_trigger .= ";
    
    -- If doctor is already booked, throw an error
    IF appointment_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This doctor is already booked at the specified time';
    END IF;
    
    -- Also check if patient already has an appointment at this time
    SELECT COUNT(*) INTO appointment_count
    FROM Appointments
    WHERE PatientID = NEW.PatientID
    AND AppointmentDate = NEW.AppointmentDate
    AND AppointmentTime = NEW.AppointmentTime";


if ($has_status) {
    $create_double_booking_trigger .= "
    AND Status NOT IN ('Cancelled', 'Completed')";
}

$create_double_booking_trigger .= ";
    
    -- If an existing appointment is found, throw an error
    IF appointment_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This patient already has an appointment at the specified time';
    END IF;
END;
";

if (!mysqli_query($conn, $create_double_booking_trigger)) {
    logError("Failed to create double booking prevention trigger: " . mysqli_error($conn));
} else {
    echo "<div class='alert alert-info'>Double booking prevention trigger created and database constraint added!</div>";
}


$check_book_appointment_procedure = "SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name = 'book_appointment'";
$book_appointment_result = mysqli_query($conn, $check_book_appointment_procedure);

if (mysqli_num_rows($book_appointment_result) == 0) {
    $create_book_appointment_procedure = "
    CREATE PROCEDURE book_appointment(
        IN p_patient_id INT,
        IN p_doctor_id INT,
        IN p_appointment_date DATE,
        IN p_appointment_time TIME,
        IN p_reason VARCHAR(255),
        OUT p_success BOOLEAN,
        OUT p_message VARCHAR(255),
        OUT p_appointment_id INT
    )
    BEGIN
        DECLARE appointment_conflict INT DEFAULT 0;
        DECLARE doctor_available BOOLEAN DEFAULT TRUE;
        DECLARE patient_exists BOOLEAN DEFAULT FALSE;
        DECLARE doctor_exists BOOLEAN DEFAULT FALSE;
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION, SQLWARNING
        BEGIN
            GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE, @error_msg = MESSAGE_TEXT;
            
            SET p_success = FALSE;
            
            -- Check if this is the double booking error from our trigger
            IF @sqlstate = '45000' THEN
                SET p_message = @error_msg;
            ELSE
                SET p_message = CONCAT('Database error: ', @error_msg);
            END IF;
            
            ROLLBACK;
        END;
        
        START TRANSACTION;
        
        -- Check if patient exists
        SELECT COUNT(*) > 0 INTO patient_exists FROM Patients WHERE PatientID = p_patient_id;
        IF NOT patient_exists THEN
            SET p_success = FALSE;
            SET p_message = 'Patient does not exist';
            ROLLBACK;
            LEAVE book_appointment;
        END IF;
        
        -- Check if doctor exists
        SELECT COUNT(*) > 0 INTO doctor_exists FROM Doctors WHERE DoctorID = p_doctor_id;
        IF NOT doctor_exists THEN
            SET p_success = FALSE;
            SET p_message = 'Doctor does not exist';
            ROLLBACK;
            LEAVE book_appointment;
        END IF;
        
        -- Check for conflicts (patient already booked)
        SELECT COUNT(*) INTO appointment_conflict
        FROM Appointments
        WHERE PatientID = p_patient_id
        AND AppointmentDate = p_appointment_date
        AND AppointmentTime = p_appointment_time";

    
    if ($has_status) {
        $create_book_appointment_procedure .= "
                AND Status NOT IN ('Cancelled', 'Completed')";
    }

    $create_book_appointment_procedure .= ";
        
        IF appointment_conflict > 0 THEN
            SET p_success = FALSE;
            SET p_message = 'Patient already has an appointment at this time';
            ROLLBACK;
            LEAVE book_appointment;
        END IF;
        
        -- Check doctor availability
        SELECT COUNT(*) INTO appointment_conflict
        FROM Appointments
        WHERE DoctorID = p_doctor_id
        AND AppointmentDate = p_appointment_date
        AND AppointmentTime = p_appointment_time";

    
    if ($has_status) {
        $create_book_appointment_procedure .= "
                AND Status NOT IN ('Cancelled', 'Completed')";
    }

    $create_book_appointment_procedure .= ";
        
        IF appointment_conflict > 0 THEN
            SET p_success = FALSE;
            SET p_message = 'Doctor is not available at this time';
            ROLLBACK;
            LEAVE book_appointment;
        END IF;
        
        -- All checks passed, try to insert the appointment
        -- If the trigger finds a conflict, it will raise an error that our handler will catch
        INSERT INTO Appointments (PatientID, DoctorID, AppointmentDate, AppointmentTime, Reason";

    
    if ($has_status) {
        $create_book_appointment_procedure .= ", Status";
    }

    $create_book_appointment_procedure .= ", CreatedAt)
        VALUES (p_patient_id, p_doctor_id, p_appointment_date, p_appointment_time, p_reason";

    
    if ($has_status) {
        $create_book_appointment_procedure .= ", 'Scheduled'";
    }

    $create_book_appointment_procedure .= ", NOW());
        
        -- Get the inserted appointment ID
        SET p_appointment_id = LAST_INSERT_ID();
        
        -- Set success output parameters
        SET p_success = TRUE;
        SET p_message = 'Appointment booked successfully';
        
        COMMIT;
    END;
    ";

    if (!mysqli_query($conn, $create_book_appointment_procedure)) {
        logError("Failed to create book appointment procedure: " . mysqli_error($conn));
    } else {
        echo "<div class='alert alert-info'>Safe appointment booking procedure created successfully!</div>";
    }
}

echo "<div class='alert alert-success'>Advanced SQL features have been set up successfully!</div>";
?>