<?php
session_start();
require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Doctor') {
    $_SESSION['login_error'] = "Please log in as a doctor to access this page";
    header("Location: ../../doctorLogin/index.php");
    exit();
}


include_once('setup_advanced_sql.php');

$success_message = '';
$error_message = '';
$appointment_id = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
    $appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
    $appointment_time = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';

    
    if ($patient_id <= 0 || $doctor_id <= 0 || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $error_message = 'Please fill in all required fields';
    } else {
        
        $check_conflicts = "
            SELECT COUNT(*) AS conflict_count
            FROM Appointments
            WHERE (
                (PatientID = ? AND AppointmentDate = ? AND AppointmentTime = ? AND Status NOT IN ('Cancelled', 'Completed'))
                OR 
                (DoctorID = ? AND AppointmentDate = ? AND AppointmentTime = ? AND Status NOT IN ('Cancelled', 'Completed'))
            )
        ";

        $check_stmt = mysqli_prepare($conn, $check_conflicts);
        mysqli_stmt_bind_param($check_stmt, "ississs", $patient_id, $appointment_date, $appointment_time, $doctor_id, $appointment_date, $appointment_time);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $conflict_data = mysqli_fetch_assoc($check_result);
        mysqli_stmt_close($check_stmt);

        if ($conflict_data['conflict_count'] > 0) {
            
            $error_message = 'Cannot book appointment: Either the patient already has an appointment at this time or the doctor is not available';
        } else {
            
            $call_procedure = "CALL book_appointment(?, ?, ?, ?, ?, @p_success, @p_message, @p_appointment_id)";
            $stmt = mysqli_prepare($conn, $call_procedure);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason);

                
                try {
                    $exec_result = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    if ($exec_result) {
                        
                        $result = mysqli_query($conn, "SELECT @p_success AS success, @p_message AS message, @p_appointment_id AS appointment_id");
                        $row = mysqli_fetch_assoc($result);

                        if ($row['success']) {
                            $success_message = $row['message'];
                            $appointment_id = $row['appointment_id'];
                        } else {
                            $error_message = $row['message'];
                        }
                    } else {
                        $error_message = 'Error executing procedure: ' . mysqli_error($conn);
                    }
                } catch (Exception $e) {
                    $error_message = 'Database error: ' . $e->getMessage();
                }
            } else {
                $error_message = 'Error preparing statement: ' . mysqli_error($conn);
            }
        }
    }
}


$patients_query = "SELECT PatientID, CONCAT(FirstName, ' ', LastName) AS PatientName FROM Patients ORDER BY LastName, FirstName";
$patients_result = mysqli_query($conn, $patients_query);

$doctors_query = "SELECT DoctorID, CONCAT(FirstName, ' ', LastName) AS DoctorName FROM Doctors ORDER BY LastName, FirstName";
$doctors_result = mysqli_query($conn, $doctors_query);


$recent_bookings_query = "
SELECT 
    a.AppointmentID,
    CONCAT(p.FirstName, ' ', p.LastName) AS PatientName,
    CONCAT(d.FirstName, ' ', d.LastName) AS DoctorName,
    a.AppointmentDate,
    a.AppointmentTime,
    a.Status,
    a.Reason
FROM Appointments a
JOIN Patients p ON a.PatientID = p.PatientID
JOIN Doctors d ON a.DoctorID = d.DoctorID
ORDER BY a.CreatedAt DESC
LIMIT 10
";
$recent_bookings_result = mysqli_query($conn, $recent_bookings_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment Demo | Hospital Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f8fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eaeaea;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-body {
            padding: 20px;
        }

        .form-label {
            font-weight: 500;
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0a58ca;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h1 class="mb-4">Book Appointment Demo</h1>
                <p class="text-muted">This page demonstrates the use of PL/SQL stored procedure with validation checks
                    to prevent double booking.</p>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?> (Appointment ID: <?php echo $appointment_id; ?>)
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        Book New Appointment
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="patient_id" class="form-label">Patient</label>
                                    <select name="patient_id" id="patient_id" class="form-select" required>
                                        <option value="">-- Select Patient --</option>
                                        <?php while ($patient = mysqli_fetch_assoc($patients_result)): ?>
                                            <option value="<?php echo $patient['PatientID']; ?>">
                                                <?php echo htmlspecialchars($patient['PatientName']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="doctor_id" class="form-label">Doctor</label>
                                    <select name="doctor_id" id="doctor_id" class="form-select" required>
                                        <option value="">-- Select Doctor --</option>
                                        <?php while ($doctor = mysqli_fetch_assoc($doctors_result)): ?>
                                            <option value="<?php echo $doctor['DoctorID']; ?>">
                                                <?php echo htmlspecialchars($doctor['DoctorName']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="appointment_date" class="form-label">Appointment Date</label>
                                    <input type="date" class="form-control" id="appointment_date"
                                        name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_time" class="form-label">Appointment Time</label>
                                    <select name="appointment_time" id="appointment_time" class="form-select" required>
                                        <option value="">-- Select Time --</option>
                                        <option value="09:00:00">9:00 AM</option>
                                        <option value="10:00:00">10:00 AM</option>
                                        <option value="11:00:00">11:00 AM</option>
                                        <option value="12:00:00">12:00 PM</option>
                                        <option value="14:00:00">2:00 PM</option>
                                        <option value="15:00:00">3:00 PM</option>
                                        <option value="16:00:00">4:00 PM</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Appointment</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="book" class="btn btn-primary">Book Appointment</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        Recent Bookings
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($recent_bookings_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient</th>
                                            <th>Doctor</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = mysqli_fetch_assoc($recent_bookings_result)): ?>
                                            <tr>
                                                <td><?php echo $booking['AppointmentID']; ?></td>
                                                <td><?php echo htmlspecialchars($booking['PatientName']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['DoctorName']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['AppointmentDate'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($booking['AppointmentTime'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php
                                                    echo $booking['Status'] === 'Completed' ? 'success' :
                                                        ($booking['Status'] === 'Scheduled' ? 'primary' :
                                                            ($booking['Status'] === 'Cancelled' ? 'danger' : 'secondary'));
                                                    ?>">
                                                        <?php echo $booking['Status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars(substr($booking['Reason'], 0, 30)) . (strlen($booking['Reason']) > 30 ? '...' : ''); ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No recent bookings found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        PL/SQL Code Explanation
                    </div>
                    <div class="card-body">
                        <h5>Trigger: prevent_double_booking</h5>
                        <p>This trigger runs BEFORE INSERT on the Appointments table and prevents double-booking:</p>
                        <ul>
                            <li>Checks if the patient already has an appointment with this doctor at the same time</li>
                            <li>Checks if the doctor is already booked with any patient at the same time</li>
                            <li>If either condition is true, throws a MySQL error to abort the insert operation</li>
                        </ul>

                        <h5>Stored Procedure: book_appointment</h5>
                        <p>This procedure safely books appointments with comprehensive validation:</p>
                        <ul>
                            <li>Uses a transaction to ensure data consistency</li>
                            <li>Validates patient and doctor existence</li>
                            <li>Checks for appointment conflicts</li>
                            <li>Uses OUT parameters to communicate success/failure and messages</li>
                            <li>Returns the new appointment ID on success</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <a href="index.php" class="btn btn-secondary">Back to Analytics Dashboard</a>
                </div>

                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'Admin'): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-warning text-white">
                            <i class="fas fa-tools me-2"></i> Admin Tools
                        </div>
                        <div class="card-body">
                            <p><strong>Notice:</strong> If you're seeing duplicate appointments in the system, use the
                                utility below to clean up the database and enforce constraints to prevent future duplicates.
                            </p>
                            <a href="fix_duplicates.php" class="btn btn-warning">
                                <i class="fas fa-broom me-2"></i> Fix Duplicate Appointments
                            </a>
                            <p class="mt-2 small text-muted">This tool will identify and remove duplicate appointments, then
                                add a unique constraint to the database to prevent future duplicates.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>