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


$login_id = $_SESSION['user_id'];


$columns_query = "SHOW COLUMNS FROM Doctors";
$columns_result = mysqli_query($conn, $columns_query);

if (!$columns_result) {
    
    $table_check_query = "SHOW TABLES LIKE 'Doctors'";
    $table_result = mysqli_query($conn, $table_check_query);

    if (mysqli_num_rows($table_result) == 0) {
        
        $create_table_query = "CREATE TABLE Doctors (
            DoctorID INT AUTO_INCREMENT PRIMARY KEY,
            LoginID INT NOT NULL,
            DoctorName VARCHAR(100),
            Specialty VARCHAR(50),
            Email VARCHAR(100),
            Phone VARCHAR(20),
            Gender VARCHAR(20),
            Qualification VARCHAR(100),
            Address TEXT,
            JoinDate DATE,
            Bio TEXT
        )";

        if (!mysqli_query($conn, $create_table_query)) {
            logError("Failed to create Doctors table: " . mysqli_error($conn));
        }

        
        $default_doctor_name = 'Dr. Unknown';
        $default_specialty = 'General';
        $default_email = $_SESSION['email'] ?? 'unknown@example.com';
        $default_join_date = date('Y-m-d');

        $create_doctor_query = "INSERT INTO Doctors (LoginID, DoctorName, Specialty, Email, JoinDate) 
                               VALUES ('$login_id', '$default_doctor_name', '$default_specialty', '$default_email', '$default_join_date')";

        if (!mysqli_query($conn, $create_doctor_query)) {
            logError("Failed to create doctor record: " . mysqli_error($conn));
        }

        
        $doctor['DoctorID'] = mysqli_insert_id($conn);
    } else {
        logError("Doctors table exists but couldn't fetch columns: " . mysqli_error($conn));
    }

    
    $login_id_column = 'LoginID';
    $doctor_query = "SELECT * FROM Doctors WHERE $login_id_column = '$login_id'";
} else {
    
    $login_id_column = 'LoginID'; 
    $columns = [];

    while ($column = mysqli_fetch_assoc($columns_result)) {
        $columns[] = $column['Field'];
    }

    
    foreach ($columns as $column) {
        if (strtolower($column) === 'loginid') {
            $login_id_column = $column;
            break;
        } else if (strtolower($column) === 'login_id') {
            $login_id_column = $column;
            break;
        } else if (strtolower($column) === 'doctorid' && $login_id == $_SESSION['user_id']) {
            
            $login_id_column = $column;
            break;
        }
    }

    
    $doctor_query = "SELECT * FROM Doctors WHERE $login_id_column = '$login_id'";
}


$doctor_result = mysqli_query($conn, $doctor_query);


$doctor = [
    'DoctorID' => $login_id,
    'DoctorName' => 'Dr. Unknown',
    'Specialty' => 'General',
    'Email' => $_SESSION['email'] ?? 'unknown@example.com',
    'Phone' => 'Not provided',
    'Gender' => 'Not specified',
    'Qualification' => 'Not provided',
    'Address' => 'Not provided',
    'JoinDate' => date('Y-m-d'),
    'Bio' => 'No information provided'
];


if ($doctor_result && mysqli_num_rows($doctor_result) > 0) {
    $db_doctor = mysqli_fetch_assoc($doctor_result);

    
    $doctor['DoctorID'] = $db_doctor['DoctorID'] ?? ($db_doctor['doctorid'] ?? $login_id);
    $doctor['DoctorName'] = $db_doctor['DoctorName'] ?? ($db_doctor['doctorname'] ?? ($db_doctor['Name'] ?? ($db_doctor['name'] ?? 'Dr. Unknown')));
    $doctor['Specialty'] = $db_doctor['Specialty'] ?? ($db_doctor['specialty'] ?? 'General');
    $doctor['Email'] = $db_doctor['Email'] ?? ($db_doctor['email'] ?? ($_SESSION['email'] ?? 'unknown@example.com'));
    $doctor['Phone'] = $db_doctor['Phone'] ?? ($db_doctor['phone'] ?? 'Not provided');
    $doctor['Gender'] = $db_doctor['Gender'] ?? ($db_doctor['gender'] ?? 'Not specified');
    $doctor['Qualification'] = $db_doctor['Qualification'] ?? ($db_doctor['qualification'] ?? 'Not provided');
    $doctor['Address'] = $db_doctor['Address'] ?? ($db_doctor['address'] ?? 'Not provided');
    $doctor['JoinDate'] = $db_doctor['JoinDate'] ?? ($db_doctor['joindate'] ?? date('Y-m-d'));
    $doctor['Bio'] = $db_doctor['Bio'] ?? ($db_doctor['bio'] ?? 'No information provided');
} else {
    
    if (!isset($doctor['DoctorID']) || $doctor['DoctorID'] == $login_id) {
        
        $create_doctor_query = "INSERT INTO Doctors (LoginID, DoctorName, Specialty, Email, JoinDate) 
                               VALUES ('$login_id', '{$doctor['DoctorName']}', '{$doctor['Specialty']}', '{$doctor['Email']}', '{$doctor['JoinDate']}')";

        if (!mysqli_query($conn, $create_doctor_query)) {
            logError("Failed to create doctor record: " . mysqli_error($conn));
        } else {
            $doctor['DoctorID'] = mysqli_insert_id($conn);
        }
    }
}


$patients_count = 0;
$patients_query = "SELECT COUNT(*) as total FROM Appointments WHERE DoctorID = '{$doctor['DoctorID']}'";
$patients_result = mysqli_query($conn, $patients_query);

if ($patients_result && mysqli_num_rows($patients_result) > 0) {
    $patients_count = mysqli_fetch_assoc($patients_result)['total'];
}


$years_exp = 0;
if (!empty($doctor['JoinDate']) && $doctor['JoinDate'] != 'Not provided') {
    $join_date = new DateTime($doctor['JoinDate']);
    $now = new DateTime();
    $diff = $join_date->diff($now);
    $years_exp = $diff->y;
}


$formatted_doctor_id = 'DOC-' . date('Y') . '-' . $doctor['DoctorID'];


$name_parts = explode(' ', $doctor['DoctorName']);
$first_name = $name_parts[0] ?? '';
$last_name = '';
if (count($name_parts) > 1) {
    array_shift($name_parts);
    $last_name = implode(' ', $name_parts);
}


if (stripos($first_name, 'Dr.') === 0) {
    $first_name = substr($first_name, 3);
}
if (stripos($first_name, 'Dr') === 0) {
    $first_name = substr($first_name, 2);
}
$first_name = trim($first_name);


$rating = "4.8";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #5b56e8;
            --secondary-color: #3e398f;
            --accent-color: #6c63ff;
            --text-color: #333;
            --text-light: #666;
            --bg-color: #f5f7fa;
            --card-bg: #ffffff;
            --sidebar-width: 280px;
            --header-height: 70px;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --radius: 12px;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196f3;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .desktop {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

       
        .choiceSection {
            width: var(--sidebar-width);
            background-color: #ffffff;
            box-shadow: 0px 3px 15px rgba(0, 0, 0, 0.15);
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 100;
        }

        .logo {
            padding: 20px;
            background-color: rgba(114, 96, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .logo img {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
        }

        .logo h6 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: var(--text-color);
        }

        .menu-items {
            padding: 20px 0;
            flex-grow: 1;
        }

        .menu-items div {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .menu-items div img {
            width: 22px;
            height: 22px;
            margin-right: 15px;
        }

        .menu-items div h6 {
            margin: 0;
            font-size: 15px;
            font-weight: 500;
            color: #555;
        }

        .menu-items div:hover {
            background-color: rgba(114, 96, 255, 0.1);
        }

        .menu-items div:hover h6 {
            color: var(--primary-color);
        }

        .menu-items div.active {
            background-color: var(--primary-color);
        }

        .menu-items div.active h6 {
            color: white;
        }

        .logout {
            padding: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .logout a {
            display: flex;
            align-items: center;
            color: #ff6060;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        .logout img {
            width: 22px;
            height: 22px;
            margin-right: 15px;
        }

        .logout a:hover {
            background-color: rgba(255, 96, 96, 0.1);
        }

       
        .content-area {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
        }

       
        .navbar {
            height: var(--header-height);
            background: var(--card-bg);
            box-shadow: var(--shadow);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 5;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-link {
            color: var(--text-color);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
        }

       
        .card {
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 30px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-light);
            font-weight: 500;
            padding: 10px 15px;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: transparent;
            border-bottom: 2px solid var(--primary-color);
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

       
        @media (max-width: 992px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .choiceSection {
                width: 70px;
                overflow: hidden;
            }

            .menu-items div h6,
            .logout h6,
            .logo h6 {
                display: none;
            }

            .menu-items div img,
            .logout img {
                margin-right: 0;
            }

            .content-area {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
        }
    </style>
</head>

<body>
    <div class="desktop">
        <div class="choiceSection">
            <div>
                <div class="logo">
                    <img src="icons/logo.png" alt="Hospital Logo"> 
                    <h6>Hospital Management</h6>
                </div>

                <div class="menu-items">
                    <div onclick="window.location.href='../dashboard/index.php'">
                        <img src="icons/dashboard.png" alt="dashboard">
                        <h6>Dashboard</h6>
                    </div>
                    <div onclick="window.location.href='../appointments/index.php'">
                        <img src="icons\appointment.png" alt="appointments">
                        <h6>Appointments</h6>
                    </div>
                    <div onclick="window.location.href='../patients/index.php'">
                        <img src="icons\patient.png" alt="patients">
                        <h6>Patients</h6>
                    </div>
                    
                    
                    <div onclick="window.location.href='../profile/index.php'" class="active">
                        <img src="icons/profile.png" alt="profile">
                        <h6>Profile</h6>
                    </div>
                    <!-- Add new Medical Records button -->
                    <div onclick="window.location.href='../medicalRecords/view_medical_records.php'">
                        <img src="icons\medicine.png" alt="Medical Records">
                        <h6>Medical Records</h6>
                    </div>
                </div>
            </div>
            <div class="logout">
                <a href="../../logout.php">
                    <img src="icons/logout.png" alt="logout">
                    <h6>Logout</h6>
                </a>
            </div>
        </div>

        <div class="content-area">
            <div class="navbar">
                <div class="logo">
                    <h6>Seattle Grace Hospital</h6>
                </div>
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/index.php">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../patients/index.php">MY PATIENTS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../appointments/index.php">APPOINTMENTS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../profile/index.php">PROFILE</a>
                    </li>
                </ul>
            </div>

            <!-- Toast container for notifications -->
            <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
            </div>

            <!-- Profile Content -->
            <div class="container-fluid px-0">
                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="page-title mb-0">My Profile</h4>
                        <p class="text-muted">Manage your personal and professional information</p>
                    </div>
                </div>

                <!-- Profile Card -->
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab"
                                    data-bs-target="#personal" type="button" role="tab" aria-controls="personal"
                                    aria-selected="true">Personal Information</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="professional-tab" data-bs-toggle="tab"
                                    data-bs-target="#professional" type="button" role="tab" aria-controls="professional"
                                    aria-selected="false">Professional Details</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab"
                                    data-bs-target="#security" type="button" role="tab" aria-controls="security"
                                    aria-selected="false">Account Security</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="profileTabsContent">
                            <!-- Personal Information Tab -->
                            <div class="tab-pane fade show active" id="personal" role="tabpanel"
                                aria-labelledby="personal-tab">
                                <form id="personalInfoForm">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control"
                                                value="<?php echo htmlspecialchars($first_name); ?>" disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control"
                                                value="<?php echo htmlspecialchars($last_name); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control"
                                                value="<?php echo htmlspecialchars($doctor['Email']); ?>" disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control"
                                                value="<?php echo htmlspecialchars($doctor['Phone']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Doctor ID</label>
                                            <input type="text" class="form-control"
                                                value="<?php echo htmlspecialchars($formatted_doctor_id); ?>" disabled>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Gender</label>
                                            <input type="text" class="form-control"
                                                value="<?php echo htmlspecialchars($doctor['Gender']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Specialty</label>
                                        <input type="text" class="form-control"
                                            value="<?php echo htmlspecialchars($doctor['Specialty']); ?>" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">About Me</label>
                                        <textarea class="form-control" rows="4"
                                            disabled><?php echo htmlspecialchars($doctor['Bio']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" rows="2"
                                            disabled><?php echo htmlspecialchars($doctor['Address']); ?></textarea>
                                    </div>
                                </form>
                            </div>

                            <!-- Professional Details Tab -->
                            <div class="tab-pane fade" id="professional" role="tabpanel"
                                aria-labelledby="professional-tab">
                                <div class="education-section mb-4">
                                    <h4>Education</h4>
                                    <div class="education-item">
                                        <div class="row mb-3">
                                            <div class="col-md-5">
                                                <label class="form-label">Degree/Certificate</label>
                                                <input type="text" class="form-control"
                                                    value="<?php echo htmlspecialchars($doctor['Qualification']); ?>"
                                                    disabled>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">Institution</label>
                                                <input type="text" class="form-control" value="Medical University"
                                                    disabled>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Year</label>
                                                <input type="text" class="form-control"
                                                    value="<?php echo date('Y', strtotime('-' . $years_exp . ' years')); ?>"
                                                    disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="certifications-section mb-4">
                                    <h4>Certifications</h4>
                                    <div class="certification-item">
                                        <div class="row mb-3">
                                            <div class="col-md-5">
                                                <label class="form-label">Certification</label>
                                                <input type="text" class="form-control" value="Medical License"
                                                    disabled>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">Organization</label>
                                                <input type="text" class="form-control" value="Medical Board" disabled>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Year</label>
                                                <input type="text" class="form-control"
                                                    value="<?php echo date('Y', strtotime('-' . ($years_exp - 1) . ' years')); ?>"
                                                    disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                <div class="mb-4">
                                    <h4>Change Password</h4>
                                    <form id="passwordChangeForm">
                                        <div class="mb-3">
                                            <label for="currentPassword" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="currentPassword"
                                                name="currentPassword" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="newPassword" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="newPassword"
                                                name="newPassword" required>
                                            <div class="form-text">Password must be at least 8 characters long.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirmPassword"
                                                name="confirmPassword" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Change Password</button>
                                        <div id="passwordChangeMessage" class="mt-3"></div>
                                    </form>
                                </div>

                                <div class="mb-4">
                                    <h4>Notification Settings</h4>
                                    <p class="text-muted mb-4">Notification preferences can be updated by the
                                        system administrator.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                    document.getElementById('content').classList.toggle('active');
                });
            }

            
            function checkWidth() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('active');
                    document.getElementById('content').classList.add('active');
                } else {
                    sidebar.classList.remove('active');
                    document.getElementById('content').classList.remove('active');
                }
            }

            
            checkWidth();

            
            window.addEventListener('resize', checkWidth);

            
            const passwordChangeForm = document.getElementById('passwordChangeForm');
            const passwordChangeMessage = document.getElementById('passwordChangeMessage');

            if (passwordChangeForm) {
                passwordChangeForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    
                    const newPassword = document.getElementById('newPassword').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;

                    if (newPassword.length < 8) {
                        passwordChangeMessage.innerHTML = '<div class="alert alert-danger">Password must be at least 8 characters long.</div>';
                        return;
                    }

                    if (newPassword !== confirmPassword) {
                        passwordChangeMessage.innerHTML = '<div class="alert alert-danger">New passwords do not match.</div>';
                        return;
                    }

                    
                    const formData = new FormData(passwordChangeForm);

                    
                    fetch('../../../Backend/doctor/change_password.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                passwordChangeMessage.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                                passwordChangeForm.reset();
                            } else {
                                passwordChangeMessage.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                            }
                        })
                        .catch(error => {
                            passwordChangeMessage.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
                            console.error('Error:', error);
                        });
                });
            }
        });
    </script>
</body>

</html>