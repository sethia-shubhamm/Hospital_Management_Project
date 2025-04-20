<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Doctor') {
    $_SESSION['login_error'] = "Please log in as a doctor to access this page";
    header("Location: ../../login.php");
    exit();
}


require_once '../../../db_connect.php';


$login_id = $_SESSION['user_id'];
$email = $_SESSION['email'];


$doctor_query = "SELECT * FROM Doctors WHERE DoctorID = '$login_id'";
$doctor_result = mysqli_query($conn, $doctor_query);

if ($doctor_result && mysqli_num_rows($doctor_result) > 0) {
    $doctor = mysqli_fetch_assoc($doctor_result);
    $doctorName = $doctor['DoctorName'];
    $specialty = $doctor['Specialty'];
} else {
    $doctorName = "Dr. " . ucfirst(explode('@', $email)[0]);
    $specialty = "General Physician";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Patients</title>
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

       
        .search-box .input-group {
            border-radius: 50px;
            overflow: hidden;
        }

        .search-box .input-group-text,
        .search-box .form-control {
            border-color: #e0e0e0;
            background-color: #f8f9fa;
        }

        .search-box .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-color);
        }

        .card {
            border-radius: 10px;
            border: none;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
        }

        .table thead th {
            font-weight: 600;
            color: #444;
            background-color: #f8f9fa;
            border-top: none;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fe;
        }

        .dropdown-menu {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
        }
    </style>
</head>

<body>
    <div class="desktop">
        <div class="choiceSection">
            <div>
                <div class="logo">
                    <img src="icons\logo.png" alt="Hospital Logo"> 
                    <h6>Hospital Management</h6>
                </div>

                <div class="menu-items">
                    <div>
                        <img src="../dashboard/icons/dashboard.png" alt="Dashboard">
                        <a href="../dashboard/index.php" style="text-decoration: none;">
                            <h6>Dashboard</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/appointment.png" alt="Appointments">
                        <a href="../appointments/index.php" style="text-decoration: none;">
                            <h6>Appointments</h6>
                        </a>
                    </div>
                    <div class="active">
                        <img src="../dashboard/icons/patient.png" alt="Patients">
                        <a href="../patients/index.php" style="text-decoration: none;">
                            <h6>Patients</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/profile.png" alt="Profile">
                        <a href="../profile/index.php" style="text-decoration: none;">
                            <h6>Profile</h6>
                        </a>
                    </div>
                    <div onclick="window.location.href='../medicalRecords/view_medical_records.php'">
                        <img src="icons/medicine.png" alt="Medical Records">
                        <h6>Medical Records</h6>
                    </div>
                </div>
            </div>
            <div class="logout">
                <a href="../../logout.php">
                    <img src="../dashboard/icons/logout.png" alt="Logout">
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
                        <a class="nav-link active" href="../patients/index.php">MY PATIENTS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../appointments/index.php">APPOINTMENTS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../profile/index.php">PROFILE</a>
                    </li>
                </ul>
            </div>

            <!-- Toast container for notifications -->
            <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
            </div>

            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="page-title mb-0">Manage Patients</h4>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">
                            <i class="fas fa-plus me-2"></i>Add New Patient
                        </button>
                    </div>
                </div>
            </div>

            <!-- Patient List Section -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">Patient List</h5>
                            <?php
                            
                            $count_query = "SELECT COUNT(DISTINCT PatientID) as total FROM Appointments WHERE DoctorID = '$login_id'";
                            $count_result = mysqli_query($conn, $count_query);
                            $patient_count = 0;

                            if ($count_result && mysqli_num_rows($count_result) > 0) {
                                $count_data = mysqli_fetch_assoc($count_result);
                                $patient_count = $count_data['total'];
                            }
                            ?>
                            <p class="text-muted small mb-0">Total Patients: <span
                                    id="patientCount"><?php echo $patient_count; ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <div class="search-box float-end">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" id="searchPatients" class="form-control border-start-0"
                                        placeholder="Search patients...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Loading spinner -->
                    <div id="loadingSpinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading patients...</p>
                    </div>

                    <!-- Error alert -->
                    <div id="errorAlert" class="alert alert-danger mx-3 mt-3" style="display: none;"></div>

                    <div class="table-responsive">
                        <table id="patientTable" class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Phone</th>
                                    <th>Last Visit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="patientTableBody">
                                <?php
                                
                                $patients_query = "SELECT DISTINCT p.*, 
                                                  MAX(a.AppointmentDate) as LastVisit,
                                                  (SELECT AppointmentDate FROM Appointments 
                                                   WHERE PatientID = p.PatientID AND DoctorID = '$login_id' AND AppointmentDate >= CURDATE() 
                                                   ORDER BY AppointmentDate ASC LIMIT 1) as NextAppointment
                                                  FROM Patients p
                                                  JOIN Appointments a ON p.PatientID = a.PatientID
                                                  WHERE a.DoctorID = '$login_id'
                                                  GROUP BY p.PatientID
                                                  ORDER BY NextAppointment IS NULL, NextAppointment ASC, LastVisit DESC";

                                $patients_result = mysqli_query($conn, $patients_query);

                                if ($patients_result && mysqli_num_rows($patients_result) > 0) {
                                    while ($patient = mysqli_fetch_assoc($patients_result)) {
                                        $patientID = $patient['PatientID'];
                                        $patientName = $patient['PatientName'];
                                        $age = isset($patient['Age']) && !empty($patient['Age']) ? $patient['Age'] : '-';
                                        $gender = isset($patient['Gender']) && !empty($patient['Gender']) ? $patient['Gender'] : '-';
                                        $phone = isset($patient['Phone']) && !empty($patient['Phone']) ? $patient['Phone'] : '-';
                                        $lastVisit = date('M d, Y', strtotime($patient['LastVisit']));

                                        
                                        $today = date('Y-m-d');
                                        $upcoming_query = "SELECT * FROM Appointments 
                                                          WHERE PatientID = '$patientID' 
                                                          AND DoctorID = '$login_id' 
                                                          AND AppointmentDate >= '$today'
                                                          ORDER BY AppointmentDate ASC LIMIT 1";

                                        $upcoming_result = mysqli_query($conn, $upcoming_query);
                                        $status = "Inactive";
                                        $statusClass = "bg-secondary";

                                        if ($upcoming_result && mysqli_num_rows($upcoming_result) > 0) {
                                            $status = "Active";
                                            $statusClass = "bg-success";
                                        }

                                        echo "<tr>
                                                <td>
                                                    <div class='d-flex align-items-center'>
                                                        <div>
                                                            <h6 class='mb-0'>$patientName</h6>
                                                            <small class='text-muted'>ID: $patientID</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>$age</td>
                                                <td>$gender</td>
                                                <td>$phone</td>
                                                <td>$lastVisit</td>
                                                <td><span class='badge $statusClass'>$status</span></td>
                                                <td>
                                                    <button class='btn btn-sm btn-outline-primary me-1 view-details' data-patient-id='$patientID'>
                                                        <i class='fas fa-eye'></i>
                                                    </button>
                                                    <button class='btn btn-sm btn-outline-success me-1 schedule-appointment' data-patient-id='$patientID'>
                                                        <i class='fas fa-calendar-plus'></i>
                                                    </button>
                                                </td>
                                            </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center py-4'>No patients found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Details Modal -->
    <div class="modal fade" id="patientDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="patientName">Patient Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="patient-info">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Patient ID</small>
                                    <span id="patientDetailsId">-</span>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Age</small>
                                        <span id="patientDetailsAge">-</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Gender</small>
                                        <span id="patientDetailsGender">-</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Phone</small>
                                        <span id="patientDetailsPhone">-</span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Email</small>
                                        <span id="patientDetailsEmail">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="patientDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="details-tab" data-bs-toggle="tab"
                                data-bs-target="#details" type="button" role="tab" aria-controls="details"
                                aria-selected="true">Details</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="medical-history-tab" data-bs-toggle="tab"
                                data-bs-target="#medical-history" type="button" role="tab"
                                aria-controls="medical-history" aria-selected="false">Medical History</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appointments-tab" data-bs-toggle="tab"
                                data-bs-target="#appointments" type="button" role="tab" aria-controls="appointments"
                                aria-selected="false">Appointments</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="patientDetailsTabContent">
                        <div class="tab-pane fade show active" id="details" role="tabpanel"
                            aria-labelledby="details-tab">
                            <div class="row patient-details-view">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Address</small>
                                    <span id="patientDetailsAddress">-</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Blood Group</small>
                                    <span id="patientDetailsBloodGroup">-</span>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Allergies</small>
                                    <span id="patientDetailsAllergies">-</span>
                                </div>
                            </div>

                            <!-- Editable form (hidden by default) -->
                            <div class="patient-details-edit" style="display: none;">
                                <form id="editPatientForm">
                                    <input type="hidden" id="editPatientId" name="patient_id">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="editPatientName" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="editPatientName"
                                                name="PatientName">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="editPatientAge" class="form-label">Age</label>
                                            <input type="number" class="form-control" id="editPatientAge" name="Age"
                                                min="0" max="120">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="editPatientGender" class="form-label">Gender</label>
                                            <select class="form-select" id="editPatientGender" name="Gender">
                                                <option value="">Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="editPatientPhone" class="form-label">Phone</label>
                                            <input type="tel" class="form-control" id="editPatientPhone" name="Phone">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="editPatientEmail" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="editPatientEmail" name="Email">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="editPatientBloodGroup" class="form-label">Blood Group</label>
                                            <select class="form-select" id="editPatientBloodGroup" name="BloodGroup">
                                                <option value="">Select Blood Group</option>
                                                <option value="A+">A+</option>
                                                <option value="A-">A-</option>
                                                <option value="B+">B+</option>
                                                <option value="B-">B-</option>
                                                <option value="AB+">AB+</option>
                                                <option value="AB-">AB-</option>
                                                <option value="O+">O+</option>
                                                <option value="O-">O-</option>
                                            </select>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label for="editPatientAddress" class="form-label">Address</label>
                                            <textarea class="form-control" id="editPatientAddress" name="Address"
                                                rows="2"></textarea>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label for="editPatientAllergies" class="form-label">Allergies</label>
                                            <textarea class="form-control" id="editPatientAllergies" name="Allergies"
                                                rows="2"></textarea>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="medical-history" role="tabpanel"
                            aria-labelledby="medical-history-tab">
                            <div id="medicalHistoryContainer">
                                <!-- Medical history will be loaded via AJAX -->
                                <p class="text-center py-3">Loading medical history...</p>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="appointments" role="tabpanel" aria-labelledby="appointments-tab">
                            <div id="appointmentsContainer">
                                <!-- Appointments will be loaded via AJAX -->
                                <p class="text-center py-3">Loading appointments...</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" id="editPatientBtn">Edit Patient</button>
                    <button type="button" class="btn btn-success" id="savePatientBtn" style="display: none;">Save
                        Changes</button>
                    <button type="button" class="btn btn-primary schedule-appointment-modal">Schedule
                        Appointment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Patient Modal (Simplified for this example) -->
    <div class="modal fade" id="addPatientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPatientForm">
                        <div class="mb-3">
                            <label for="patientName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="patientName" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="patientAge" class="form-label">Age</label>
                                <input type="number" class="form-control" id="patientAge" min="0" max="120" required>
                            </div>
                            <div class="col-md-6">
                                <label for="patientGender" class="form-label">Gender</label>
                                <select class="form-select" id="patientGender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="patientPhone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="patientPhone" required>
                        </div>
                        <div class="mb-3">
                            <label for="patientEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="patientEmail">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePatientBtn">Save Patient</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Appointment Modal -->
    <div class="modal fade" id="scheduleAppointmentModal" tabindex="-1" aria-labelledby="scheduleAppointmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleAppointmentModalLabel">Schedule Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleAppointmentForm">
                        <input type="hidden" id="appointment_patient_id" name="patient_id">
                        <div class="mb-3">
                            <label for="patient_name_display" class="form-label">Patient</label>
                            <input type="text" class="form-control" id="patient_name_display" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="appointment_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="appointment_time" class="form-label">Time</label>
                            <input type="time" class="form-control" id="appointment_time" name="appointment_time"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="appointment_type" class="form-label">Type</label>
                            <select class="form-select" id="appointment_type" name="appointment_type" required>
                                <option value="">Select type...</option>
                                <option value="Check-up">Check-up</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Consultation">Consultation</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="appointment_description" class="form-label">Description</label>
                            <textarea class="form-control" id="appointment_description" name="appointment_description"
                                rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveAppointmentBtn">Schedule</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container for notifications -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto" id="toast-title">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toast-message">

            </div>
        </div>
    </div>

    <!-- Add Medical History Modal -->
    <div class="modal fade" id="medicalHistoryModal" tabindex="-1" aria-labelledby="medicalHistoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="medicalHistoryModalLabel">Patient Medical History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="medicalHistoryForm">
                        <input type="hidden" id="mh_patient_id" name="patient_id">

                        <div class="mb-3">
                            <label for="allergies" class="form-label">Allergies</label>
                            <textarea class="form-control" id="allergies" name="allergies" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="medical_conditions" class="form-label">Medical Conditions</label>
                            <textarea class="form-control" id="medical_conditions" name="medical_conditions"
                                rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="medications" class="form-label">Current Medications</label>
                            <textarea class="form-control" id="medications" name="medications" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="surgery_history" class="form-label">Surgery History</label>
                            <textarea class="form-control" id="surgery_history" name="surgery_history"
                                rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="family_medical_history" class="form-label">Family Medical History</label>
                            <textarea class="form-control" id="family_medical_history" name="family_medical_history"
                                rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="additional_notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="additional_notes" name="additional_notes"
                                rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveMedicalHistory">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script>
        $(document).ready(function () {
            
            $('#loadingSpinner').hide();

            
            $('#sidebarToggle').on('click', function () {
                $('#sidebar').toggleClass('active');
            });

            
            $('.view-details').on('click', function () {
                const patientId = $(this).data('patient-id');
                loadPatientDetails(patientId);
            });

            
            function loadPatientDetails(patientId) {
                
                $('#patientDetailsModal').modal('show');
                $('#patientDetailsId').text('Loading...');

                
                $.ajax({
                    url: 'get_patient_details.php',
                    type: 'GET',
                    data: { patient_id: patientId },
                    dataType: 'json',
                    success: function (data) {
                        if (data.success) {
                            
                            $('#patientName').text(data.patient.PatientName);
                            $('#patientDetailsId').text(data.patient.PatientID);
                            $('#patientDetailsAge').text(data.patient.Age || '-');
                            $('#patientDetailsGender').text(data.patient.Gender || '-');
                            $('#patientDetailsPhone').text(data.patient.Phone || '-');
                            $('#patientDetailsEmail').text(data.patient.Email || '-');
                            $('#patientDetailsAddress').text(data.patient.Address || '-');
                            $('#patientDetailsBloodGroup').text(data.patient.BloodGroup || '-');
                            $('#patientDetailsAllergies').text(data.patient.Allergies || 'None recorded');

                            
                            $('#editPatientId').val(data.patient.PatientID);
                            $('#editPatientName').val(data.patient.PatientName);
                            $('#editPatientAge').val(data.patient.Age || '');
                            $('#editPatientGender').val(data.patient.Gender || '');
                            $('#editPatientPhone').val(data.patient.Phone || '');
                            $('#editPatientEmail').val(data.patient.Email || '');
                            $('#editPatientAddress').val(data.patient.Address || '');
                            $('#editPatientBloodGroup').val(data.patient.BloodGroup || '');
                            $('#editPatientAllergies').val(data.patient.Allergies || '');

                            
                            $('#medicalHistoryContainer').data('patient-id', patientId);

                            
                            loadMedicalHistory(patientId);

                            
                            loadPatientAppointments(patientId);

                            
                            $('#appointment_patient_id').val(patientId);
                            $('#patient_name_display').val(data.patient.PatientName);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    },
                    error: function () {
                        alert('Failed to load patient details. Please try again.');
                    }
                });
            }

            
            $('#editPatientBtn').on('click', function () {
                $('.patient-details-view').hide();
                $('.patient-details-edit').show();
                $(this).hide();
                $('#savePatientBtn').show();
            });

            
            $('#savePatientBtn').on('click', function () {
                
                const formData = $('#editPatientForm').serialize();

                
                $.ajax({
                    url: 'update_patient.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            
                            $('#patientName').text(response.patient.PatientName);
                            $('#patientDetailsAge').text(response.patient.Age || '-');
                            $('#patientDetailsGender').text(response.patient.Gender || '-');
                            $('#patientDetailsPhone').text(response.patient.Phone || '-');
                            $('#patientDetailsEmail').text(response.patient.Email || '-');
                            $('#patientDetailsAddress').text(response.patient.Address || '-');
                            $('#patientDetailsBloodGroup').text(response.patient.BloodGroup || '-');
                            $('#patientDetailsAllergies').text(response.patient.Allergies || 'None recorded');

                            
                            $('.patient-details-edit').hide();
                            $('.patient-details-view').show();
                            $('#savePatientBtn').hide();
                            $('#editPatientBtn').show();

                            
                            showToast('Patient information updated successfully', 'success');

                            
                            location.reload();
                        } else {
                            showToast('Error: ' + response.message, 'error');
                        }
                    },
                    error: function () {
                        showToast('Failed to update patient information', 'error');
                    }
                });
            });

            
            function loadMedicalHistory(patientId) {
                $('#medicalHistoryContainer').html(`
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Medical Condition</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editMedicalConditionBtn">
                                <i class="fas fa-edit me-1"></i> Edit
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="medicalConditionView">
                                <p id="medicalConditionText">Loading...</p>
                            </div>
                            <div id="medicalConditionEdit" style="display: none;">
                                <form id="medicalConditionForm">
                                    <div class="mb-3">
                                        <label for="medicalConditionInput" class="form-label">Medical Condition</label>
                                        <textarea class="form-control" id="medicalConditionInput" rows="3" placeholder="Enter patient's medical condition"></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-secondary me-2" id="cancelMedicalConditionBtn">Cancel</button>
                                        <button type="button" class="btn btn-primary" id="saveMedicalConditionBtn">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Treatment Plan</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editTreatmentBtn">
                                <i class="fas fa-edit me-1"></i> Edit
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="treatmentView">
                                <p id="treatmentText">Loading...</p>
                            </div>
                            <div id="treatmentEdit" style="display: none;">
                                <form id="treatmentForm">
                                    <div class="mb-3">
                                        <label for="treatmentInput" class="form-label">Treatment Plan</label>
                                        <textarea class="form-control" id="treatmentInput" rows="4" placeholder="Enter treatment plan details"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="medicationInput" class="form-label">Medications</label>
                                        <textarea class="form-control" id="medicationInput" rows="2" placeholder="Enter medications (name, dosage, frequency)"></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-secondary me-2" id="cancelTreatmentBtn">Cancel</button>
                                        <button type="button" class="btn btn-primary" id="saveTreatmentBtn">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                `);

                
                $.ajax({
                    url: 'get_medical_history.php',
                    type: 'GET',
                    data: { patient_id: patientId },
                    dataType: 'json',
                    success: function (data) {
                        if (data.success) {
                            
                            $('#medicalConditionText').text(data.condition || 'No medical condition recorded.');
                            $('#medicalConditionInput').val(data.condition || '');

                            
                            $('#treatmentText').html(
                                '<strong>Treatment:</strong> ' + (data.treatment || 'No treatment plan recorded.') +
                                '<br><strong>Medications:</strong> ' + (data.medications || 'No medications recorded.')
                            );
                            $('#treatmentInput').val(data.treatment || '');
                            $('#medicationInput').val(data.medications || '');
                        } else {
                            $('#medicalConditionText').text('No medical condition recorded.');
                            $('#treatmentText').html('<strong>Treatment:</strong> No treatment plan recorded.<br><strong>Medications:</strong> No medications recorded.');
                        }

                        
                        setupMedicalHistoryEditors(patientId);
                    },
                    error: function () {
                        $('#medicalConditionText').text('Failed to load medical condition data.');
                        $('#treatmentText').text('Failed to load treatment data.');
                    }
                });
            }

            
            function setupMedicalHistoryEditors(patientId) {
                
                $('#editMedicalConditionBtn').on('click', function () {
                    $('#medicalConditionView').hide();
                    $('#medicalConditionEdit').show();
                    $(this).hide();
                });

                $('#cancelMedicalConditionBtn').on('click', function () {
                    $('#medicalConditionEdit').hide();
                    $('#medicalConditionView').show();
                    $('#editMedicalConditionBtn').show();
                });

                $('#saveMedicalConditionBtn').on('click', function () {
                    const condition = $('#medicalConditionInput').val();

                    
                    $.ajax({
                        url: 'update_medical_history.php',
                        type: 'POST',
                        data: {
                            patient_id: patientId,
                            type: 'condition',
                            value: condition
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                
                                $('#medicalConditionText').text(condition || 'No medical condition recorded.');
                                $('#medicalConditionEdit').hide();
                                $('#medicalConditionView').show();
                                $('#editMedicalConditionBtn').show();

                                
                                alert('Medical condition updated successfully!');
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function () {
                            alert('Failed to update medical condition. Please try again.');
                        }
                    });
                });

                
                $('#editTreatmentBtn').on('click', function () {
                    $('#treatmentView').hide();
                    $('#treatmentEdit').show();
                    $(this).hide();
                });

                $('#cancelTreatmentBtn').on('click', function () {
                    $('#treatmentEdit').hide();
                    $('#treatmentView').show();
                    $('#editTreatmentBtn').show();
                });

                $('#saveTreatmentBtn').on('click', function () {
                    const treatment = $('#treatmentInput').val();
                    const medications = $('#medicationInput').val();

                    
                    $.ajax({
                        url: 'update_medical_history.php',
                        type: 'POST',
                        data: {
                            patient_id: patientId,
                            type: 'treatment',
                            treatment: treatment,
                            medications: medications
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                
                                $('#treatmentText').html(
                                    '<strong>Treatment:</strong> ' + (treatment || 'No treatment plan recorded.') +
                                    '<br><strong>Medications:</strong> ' + (medications || 'No medications recorded.')
                                );
                                $('#treatmentEdit').hide();
                                $('#treatmentView').show();
                                $('#editTreatmentBtn').show();

                                
                                alert('Treatment plan updated successfully!');
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function () {
                            alert('Failed to update treatment plan. Please try again.');
                        }
                    });
                });
            }

            
            function loadPatientAppointments(patientId) {
                $('#appointmentsContainer').html(`<p class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Loading appointments...</p>`);

                $.ajax({
                    url: 'get_patient_appointments.php',
                    type: 'GET',
                    data: { patient_id: patientId },
                    dataType: 'json',
                    success: function (data) {
                        if (data.success && data.appointments.length > 0) {
                            let html = '<div class="table-responsive"><table class="table table-hover table-sm">';
                            html += '<thead><tr><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th></tr></thead><tbody>';

                            data.appointments.forEach(appt => {
                                const date = new Date(appt.AppointmentDate);
                                const formattedDate = date.toLocaleDateString();

                                let statusClass = 'bg-primary';
                                if (appt.Status === 'Completed') statusClass = 'bg-success';
                                if (appt.Status === 'Cancelled') statusClass = 'bg-danger';

                                html += `<tr>
                                    <td>${formattedDate}</td>
                                    <td>${appt.AppointmentTime}</td>
                                    <td>${appt.Purpose || 'Regular checkup'}</td>
                                    <td><span class="badge ${statusClass}">${appt.Status || 'Scheduled'}</span></td>
                                </tr>`;
                            });

                            html += '</tbody></table></div>';
                            $('#appointmentsContainer').html(html);
                        } else {
                            $('#appointmentsContainer').html('<p class="text-center py-3">No appointments found for this patient.</p>');
                        }
                    },
                    error: function () {
                        $('#appointmentsContainer').html('<p class="text-center text-danger py-3">Failed to load appointment data.</p>');
                    }
                });
            }

            
            $('.schedule-appointment, .schedule-appointment-modal').on('click', function () {
                const patientId = $(this).data('patient-id');
                
                if (patientId) {
                    
                    $('#appointment_patient_id').val(patientId);
                    
                    $.ajax({
                        url: 'get_patient_name.php',
                        type: 'GET',
                        data: { patient_id: patientId },
                        dataType: 'json',
                        success: function (data) {
                            if (data.success) {
                                $('#patient_name_display').val(data.name);
                            }
                        }
                    });
                }
                $('#scheduleAppointmentModal').modal('show');
            });

            
            $('#savePatientBtn').on('click', function () {
                
                if ($('#addPatientForm')[0].checkValidity()) {
                    
                    const patientData = {
                        name: $('#patientName').val(),
                        age: $('#patientAge').val(),
                        gender: $('#patientGender').val(),
                        phone: $('#patientPhone').val(),
                        email: $('#patientEmail').val()
                    };

                    
                    $.ajax({
                        url: 'add_patient.php',
                        type: 'POST',
                        data: patientData,
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                alert('Patient saved successfully!');
                                $('#addPatientModal').modal('hide');
                                
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function () {
                            alert('Failed to save patient. Please try again.');
                        }
                    });
                } else {
                    $('#addPatientForm')[0].reportValidity();
                }
            });

            
            $('#saveAppointmentBtn').on('click', function () {
                
                if ($('#scheduleAppointmentForm')[0].checkValidity()) {
                    
                    const appointmentData = {
                        patient_id: $('#appointment_patient_id').val(),
                        date: $('#appointment_date').val(),
                        time: $('#appointment_time').val(),
                        purpose: $('#appointment_type').val(),
                        notes: $('#appointment_description').val()
                    };

                    
                    $.ajax({
                        url: 'save_appointment.php',
                        type: 'POST',
                        data: appointmentData,
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                alert('Appointment scheduled successfully!');
                                $('#scheduleAppointmentModal').modal('hide');
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function () {
                            alert('Failed to schedule appointment. Please try again.');
                        }
                    });
                } else {
                    $('#scheduleAppointmentForm')[0].reportValidity();
                }
            });

            
            $('#searchPatients').on('keyup', function () {
                const searchText = $(this).val().toLowerCase();
                $('#patientTableBody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(searchText) > -1);
                });
            });

            
            function showToast(message, type) {
                const toastTitle = $('#toast-title');
                const toastMessage = $('#toast-message');
                const toast = $('#toast');

                toastTitle.text(type === 'success' ? 'Success' : 'Error');
                toastMessage.text(message);

                
                toast.removeClass('bg-success bg-danger');
                toast.addClass(type === 'success' ? 'bg-success' : 'bg-danger');

                
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
            }

            
            function openMedicalHistory(patientId, patientName) {
                $('#medicalHistoryModalLabel').text(patientName + ' - Medical History');
                $('#mh_patient_id').val(patientId);

                
                $('#medicalHistoryForm')[0].reset();

                
                $.ajax({
                    url: 'get_medical_history.php',
                    type: 'GET',
                    data: { patient_id: patientId },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            if (response.hasRecord) {
                                
                                $('#allergies').val(response.record.Allergies);
                                $('#medical_conditions').val(response.record.MedicalConditions);
                                $('#medications').val(response.record.Medications);
                                $('#surgery_history').val(response.record.SurgeryHistory);
                                $('#family_medical_history').val(response.record.FamilyMedicalHistory);
                                $('#additional_notes').val(response.record.AdditionalNotes);
                            }
                        } else {
                            showToast('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        showToast('Error', 'Failed to load medical history', 'error');
                    }
                });

                $('#medicalHistoryModal').modal('show');
            }

            
            $(document).on('click', '#saveMedicalHistory', function () {
                $.ajax({
                    url: 'update_medical_history.php',
                    type: 'POST',
                    data: $('#medicalHistoryForm').serialize(),
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            showToast('Success', response.message, 'success');
                            $('#medicalHistoryModal').modal('hide');
                        } else {
                            showToast('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        showToast('Error', 'An unexpected error occurred', 'error');
                    }
                });
            });
        });
    </script>
</body>

</html>