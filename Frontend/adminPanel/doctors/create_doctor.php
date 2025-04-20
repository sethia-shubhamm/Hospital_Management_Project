<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    $_SESSION['login_error'] = "Please log in as an admin to access this page";
    header("Location: ../../login/index.php");
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, '../error_log.txt');
}


$error = '';
$success = '';
$specialties = [
    'Cardiology',
    'Dermatology',
    'Endocrinology',
    'Gastroenterology',
    'Neurology',
    'Obstetrics',
    'Oncology',
    'Ophthalmology',
    'Orthopedics',
    'Pediatrics',
    'Psychiatry',
    'Urology'
];


$check_table_query = "SHOW TABLES LIKE 'Doctors'";
$table_result = mysqli_query($conn, $check_table_query);

if (!$table_result) {
    logError("Database error checking if Doctors table exists: " . mysqli_error($conn));
}

if (mysqli_num_rows($table_result) == 0) {
    
    $create_table_query = "CREATE TABLE Doctors (
        DoctorID INT AUTO_INCREMENT PRIMARY KEY,
        LoginID INT,
        Name VARCHAR(100),
        Email VARCHAR(100),
        Phone VARCHAR(20),
        Specialty VARCHAR(50),
        Qualification VARCHAR(100),
        JoinDate DATE,
        Status VARCHAR(20) DEFAULT 'Active'
    )";

    if (!mysqli_query($conn, $create_table_query)) {
        logError("Failed to create Doctors table: " . mysqli_error($conn));
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $specialty = mysqli_real_escape_string($conn, $_POST['specialty']);
    $qualification = mysqli_real_escape_string($conn, $_POST['qualification']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $join_date = date('Y-m-d'); 

    
    if (empty($name) || empty($email) || empty($phone) || empty($specialty) || empty($qualification) || empty($_POST['password'])) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        
        $check_email_query = "SELECT * FROM Login WHERE Email = '$email'";
        $email_result = mysqli_query($conn, $check_email_query);

        if (!$email_result) {
            logError("Database error checking email: " . mysqli_error($conn));
            $error = "Database error occurred. Please try again.";
        } elseif (mysqli_num_rows($email_result) > 0) {
            $error = "Email already exists. Please use a different email.";
        } else {
            
            mysqli_autocommit($conn, FALSE);
            $transaction_successful = true;

            
            $login_query = "INSERT INTO Login (Email, Password, UserType) VALUES ('$email', '$password', 'Doctor')";
            if (!mysqli_query($conn, $login_query)) {
                logError("Failed to create login record: " . mysqli_error($conn));
                $error = "Failed to create doctor account. Please try again.";
                $transaction_successful = false;
            }

            if ($transaction_successful) {
                
                $login_id = mysqli_insert_id($conn);

                
                $doctor_query = "INSERT INTO Doctors (LoginID, Name, Email, Phone, Specialty, Qualification, JoinDate) 
                               VALUES ('$login_id', '$name', '$email', '$phone', '$specialty', '$qualification', '$join_date')";

                if (!mysqli_query($conn, $doctor_query)) {
                    logError("Failed to create doctor record: " . mysqli_error($conn));
                    $error = "Failed to create doctor record. Please try again.";
                    $transaction_successful = false;
                }
            }

            
            if ($transaction_successful) {
                mysqli_commit($conn);
                $success = "Doctor account created successfully!";

                
                $name = $email = $phone = $specialty = $qualification = '';
            } else {
                mysqli_rollback($conn);
            }

            
            mysqli_autocommit($conn, TRUE);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Doctor Account</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../dashboard/style.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            color: #2c3e50;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }

        .required-field::after {
            content: "*";
            color: red;
            margin-left: 5px;
        }

        .error-message {
            color: #e74c3c;
            background-color: #fadbd8;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success-message {
            color: #27ae60;
            background-color: #d4efdf;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include_once('../dashboard/navbar.php'); ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <ul class="list-unstyled components">
                    <li>
                        <a href="../dashboard/index.php">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="index.html">
                            <i class="bi bi-person-badge"></i>
                            <span>Doctors</span>
                        </a>
                    </li>
                    <li>
                        <a href="../patients/index.html">
                            <i class="bi bi-people"></i>
                            <span>Patients</span>
                        </a>
                    </li>
                    <li>
                        <a href="../appointments/index.html">
                            <i class="bi bi-calendar-check"></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-container">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Create Doctor Account</h1>
                    <a href="index.php" class="btn btn-secondary">Back to Doctors List</a>
                </div>

                <div class="form-container">
                    <?php if (!empty($error)): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label required-field">Doctor Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Please enter the doctor's name</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label required-field">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label required-field">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                <div class="invalid-feedback">Please enter a phone number</div>
                            </div>
                            <div class="col-md-6">
                                <label for="specialty" class="form-label required-field">Specialty</label>
                                <select class="form-select" id="specialty" name="specialty" required>
                                    <option value="" selected disabled>Select Specialty</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                        <option value="<?php echo $specialty; ?>"><?php echo $specialty; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a specialty</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="qualification" class="form-label required-field">Qualification</label>
                                <input type="text" class="form-control" id="qualification" name="qualification"
                                    required>
                                <div class="invalid-feedback">Please enter qualifications</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label required-field">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">Please enter a password</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-outline-secondary me-md-2">Reset</button>
                            <button type="submit" class="btn btn-primary">Create Doctor Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        
        (function () {
            'use strict';

            const forms = document.querySelectorAll('.needs-validation');

            Array.from(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>

</html>