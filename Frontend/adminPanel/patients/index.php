<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Admin') {
    $_SESSION['login_error'] = "Please log in as an administrator to access this page";
    header("Location: ../../adminLogin/index.php");
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = '../error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Patients'");
if (mysqli_num_rows($tableCheck) == 0) {
    logError("Patients table does not exist");
    
    $createPatientsTable = "CREATE TABLE Patients (
        PatientID INT AUTO_INCREMENT PRIMARY KEY,
        PatientName VARCHAR(100) NOT NULL,
        Email VARCHAR(100),
        Phone VARCHAR(20),
        Age INT,
        Gender VARCHAR(10),
        Address TEXT,
        MedicalHistory TEXT,
        BloodType VARCHAR(10),
        EmergencyContact VARCHAR(100),
        EmergencyPhone VARCHAR(20),
        Status VARCHAR(20) DEFAULT 'Active',
        RegisterDate DATE
    )";

    if (!mysqli_query($conn, $createPatientsTable)) {
        logError("Failed to create Patients table: " . mysqli_error($conn));
    }
}


$patientsQuery = "SELECT * FROM Patients ORDER BY PatientID DESC";
$patientsResult = mysqli_query($conn, $patientsQuery);

if (!$patientsResult) {
    logError("Failed to fetch patients: " . mysqli_error($conn));
}


$doctorsQuery = "SELECT DoctorID, DoctorName FROM Doctors ORDER BY DoctorName";
$doctorsResult = mysqli_query($conn, $doctorsQuery);

if (!$doctorsResult) {
    logError("Failed to fetch doctors: " . mysqli_error($conn));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $patientId = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $newStatus = mysqli_real_escape_string($conn, $_POST['status']);

    $updateQuery = "UPDATE Patients SET Status = '$newStatus' WHERE PatientID = '$patientId'";
    $updateResult = mysqli_query($conn, $updateQuery);

    if ($updateResult) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients Management | Seattle Grace Hospital</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
       
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .close-button {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 20px;
            cursor: pointer;
        }

        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }

        .form-group {
            flex: 0 0 50%;
            padding: 0 10px;
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .required-field::after {
            content: "*";
            color: red;
            margin-left: 5px;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            gap: 10px;
        }

        .status-dropdown {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .status-active {
            color: green;
        }

        .status-admitted {
            color: #2196f3;
        }

        .status-discharged {
            color: orange;
        }

        .status-inactive {
            color: red;
        }

       
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .status.admitted {
            background-color: rgba(33, 150, 243, 0.1);
            color: #2196f3;
        }

        .status.outpatient {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4caf50;
        }

        .status.emergency {
            background-color: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }

        .status.scheduled {
            background-color: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/logo.png" alt="Hospital Logo" class="logo"> 
                <h3>Admin Panel</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="../dashboard/index.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../doctors/index.php">
                        <i class="bi bi-person-badge"></i>
                        <span>Doctors</span>
                    </a>
                </li>
                <li class="active">
                    <a href="index.php">
                        <i class="bi bi-people"></i>
                        <span>Patients</span>
                    </a>
                </li>
                <li>
                    <a href="../appointments/index.php">
                        <i class="bi bi-calendar-check"></i>
                        <span>Appointments</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../logout.php" class="logout">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="bi bi-list"></i>
                    </button>
                    <h3 style="margin-right: 500px;">Patients Management</h3>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <input type="text" id="patientSearch" placeholder="Search patients...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                    <div class="admin-profile">
                        <img src="../assets/img/logo.png" alt="Admin Profile"> 
                        <div class="profile-info">
                            <span class="name">Admin User</span>
                            <span class="role">System Administrator</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="content-header-actions">
                    <h2>Manage Patients</h2>
                    <button class="btn-primary" id="openAddPatientModal"><i class="fas fa-plus"></i> Add New
                        Patient</button>
                </div>

                <div class="filters-bar">
                    <div class="filter-group">
                        <label>Status:</label>
                        <select id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="Active">Active</option>
                            <option value="Admitted">Admitted</option>
                            <option value="Outpatient">Outpatient</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Discharged">Discharged</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Doctor:</label>
                        <select id="doctorFilter">
                            <option value="">All Doctors</option>
                            <?php if ($doctorsResult && mysqli_num_rows($doctorsResult) > 0): ?>
                                <?php while ($doctor = mysqli_fetch_assoc($doctorsResult)): ?>
                                    <option value="<?php echo $doctor['DoctorID']; ?>">
                                        <?php echo htmlspecialchars($doctor['DoctorName']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button class="btn-filter" id="applyFilters">Apply Filters</button>
                </div>

                <div class="table-card patients-table">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="select-all">
                                    </th>
                                    <th>Patient</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="patientTableBody">
                                <?php if ($patientsResult && mysqli_num_rows($patientsResult) > 0): ?>
                                    <?php while ($patient = mysqli_fetch_assoc($patientsResult)): ?>
                                        <?php
                                        
                                        $initials = '';
                                        $nameParts = explode(' ', $patient['PatientName']);
                                        foreach ($nameParts as $part) {
                                            $initials .= strtoupper(substr($part, 0, 1));
                                        }
                                        $initials = substr($initials, 0, 2);

                                        
                                        $statusClass = 'outpatient'; 
                                        if (isset($patient['Status'])) {
                                            if ($patient['Status'] == 'Admitted') {
                                                $statusClass = 'admitted';
                                            } elseif ($patient['Status'] == 'Emergency') {
                                                $statusClass = 'emergency';
                                            } elseif ($patient['Status'] == 'Scheduled') {
                                                $statusClass = 'scheduled';
                                            }
                                        }
                                        ?>
                                        <tr data-status="<?php echo htmlspecialchars($patient['Status'] ?? 'Active'); ?>">
                                            <td><input type="checkbox" class="select-row"></td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar"><?php echo $initials; ?></div>
                                                    <div>
                                                        <span
                                                            class="user-name"><?php echo htmlspecialchars($patient['PatientName']); ?></span>
                                                        <span
                                                            class="user-email"><?php echo htmlspecialchars($patient['Email'] ?? 'No email provided'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($patient['Age'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($patient['Gender'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($patient['Phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="status <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($patient['Status'] ?? 'Active'); ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <button class="action-btn edit" data-id="<?php echo $patient['PatientID']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="action-btn delete"
                                                    data-id="<?php echo $patient['PatientID']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <button class="action-btn view" data-id="<?php echo $patient['PatientID']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">No patients found. Add your first
                                            patient!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <button class="pagination-btn prev"><i class="fas fa-chevron-left"></i></button>
                        <button class="pagination-btn active">1</button>
                        <button class="pagination-btn">2</button>
                        <button class="pagination-btn">3</button>
                        <span class="pagination-ellipsis">...</span>
                        <button class="pagination-btn">10</button>
                        <button class="pagination-btn next"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>

            <footer class="content-footer">
                <p>&copy; 2024 Seattle Grace Hospital. All rights reserved.</p>
            </footer>
        </main>
    </div>

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeModal">&times;</span>
            <div class="modal-header">
                <h3>Add New Patient</h3>
            </div>
            <form id="addPatientForm" action="process_patient.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="patientName" class="form-label required-field">Patient Name</label>
                        <input type="text" id="patientName" name="patientName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="age" class="form-label">Age</label>
                        <input type="number" id="age" name="age" class="form-control" min="0" max="120">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="gender" class="form-label">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="bloodType" class="form-label">Blood Type</label>
                        <select id="bloodType" name="bloodType" class="form-control">
                            <option value="">Select Blood Type</option>
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
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Active" selected>Active</option>
                            <option value="Admitted">Admitted</option>
                            <option value="Outpatient">Outpatient</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Discharged">Discharged</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="registerDate" class="form-label">Registration Date</label>
                        <input type="date" id="registerDate" name="registerDate" class="form-control"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 0 0 100%;">
                        <label for="address" class="form-label">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 0 0 100%;">
                        <label for="medicalHistory" class="form-label">Medical History</label>
                        <textarea id="medicalHistory" name="medicalHistory" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="emergencyContact" class="form-label">Emergency Contact Name</label>
                        <input type="text" id="emergencyContact" name="emergencyContact" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="emergencyPhone" class="form-label">Emergency Contact Phone</label>
                        <input type="tel" id="emergencyPhone" name="emergencyPhone" class="form-control">
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="cancelAdd">Cancel</button>
                    <button type="submit" class="btn-primary">Add Patient</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Patient Modal -->
    <div id="viewPatientModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeViewModal">&times;</span>
            <div class="modal-header">
                <h3>Patient Details</h3>
            </div>
            <div id="patientDetails">
                <!-- Patient details will be loaded here via AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading patient details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div id="editPatientModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeEditModal">&times;</span>
            <div class="modal-header">
                <h3>Edit Patient</h3>
            </div>
            <form id="editPatientForm" action="update_patient.php" method="POST">
                <input type="hidden" id="editPatientId" name="patientId">
                <!-- Form fields similar to add patient, will be populated via JavaScript -->
                <div id="editFormContent">
                    <!-- Loading spinner -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading patient data...</p>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="cancelEdit">Cancel</button>
                    <button type="submit" class="btn-primary">Update Patient</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');

            if (sidebarCollapse) {
                sidebarCollapse.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }

            
            function checkWidth() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('active');
                } else {
                    sidebar.classList.remove('active');
                }
            }

            
            checkWidth();

            
            window.addEventListener('resize', checkWidth);

            
            const addModal = document.getElementById('addPatientModal');
            const viewModal = document.getElementById('viewPatientModal');
            const editModal = document.getElementById('editPatientModal');
            const openModalBtn = document.getElementById('openAddPatientModal');
            const closeModalBtn = document.getElementById('closeModal');
            const closeViewModalBtn = document.getElementById('closeViewModal');
            const closeEditModalBtn = document.getElementById('closeEditModal');
            const cancelAddBtn = document.getElementById('cancelAdd');
            const cancelEditBtn = document.getElementById('cancelEdit');

            openModalBtn.addEventListener('click', () => {
                addModal.style.display = 'block';
            });

            closeModalBtn.addEventListener('click', () => {
                addModal.style.display = 'none';
            });

            cancelAddBtn.addEventListener('click', () => {
                addModal.style.display = 'none';
            });

            closeViewModalBtn.addEventListener('click', () => {
                viewModal.style.display = 'none';
            });

            closeEditModalBtn.addEventListener('click', () => {
                editModal.style.display = 'none';
            });

            cancelEditBtn.addEventListener('click', () => {
                editModal.style.display = 'none';
            });

            window.addEventListener('click', (event) => {
                if (event.target === addModal) {
                    addModal.style.display = 'none';
                }
                if (event.target === viewModal) {
                    viewModal.style.display = 'none';
                }
                if (event.target === editModal) {
                    editModal.style.display = 'none';
                }
            });

            
            const statusFilter = document.getElementById('statusFilter');
            const doctorFilter = document.getElementById('doctorFilter');
            const applyFiltersBtn = document.getElementById('applyFilters');
            const patientSearch = document.getElementById('patientSearch');
            const patientRows = document.querySelectorAll('#patientTableBody tr');

            function applyFilters() {
                const status = statusFilter.value.toLowerCase();
                const doctorId = doctorFilter.value;
                const searchText = patientSearch.value.toLowerCase();

                patientRows.forEach(row => {
                    const rowStatus = row.getAttribute('data-status').toLowerCase();
                    const rowText = row.textContent.toLowerCase();

                    const matchesStatus = !status || rowStatus.includes(status);
                    const matchesSearch = !searchText || rowText.includes(searchText);

                    if (matchesStatus && matchesSearch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            applyFiltersBtn.addEventListener('click', applyFilters);
            patientSearch.addEventListener('keyup', applyFilters);

            
            const viewButtons = document.querySelectorAll('.action-btn.view');
            viewButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const patientId = this.getAttribute('data-id');

                    
                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', `get_patient_details.php?id=${patientId}`, true);
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            document.getElementById('patientDetails').innerHTML = xhr.responseText;
                            viewModal.style.display = 'block';
                        } else {
                            alert('Error loading patient details');
                        }
                    };
                    xhr.send();
                });
            });

            
            const editButtons = document.querySelectorAll('.action-btn.edit');
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const patientId = this.getAttribute('data-id');
                    document.getElementById('editPatientId').value = patientId;

                    
                    const xhr = new XMLHttpRequest();
                    xhr.open('GET', `get_patient_edit_form.php?id=${patientId}`, true);
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            document.getElementById('editFormContent').innerHTML = xhr.responseText;
                            editModal.style.display = 'block';
                        } else {
                            alert('Error loading patient data');
                        }
                    };
                    xhr.send();
                });
            });

            
            const deleteButtons = document.querySelectorAll('.action-btn.delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const patientId = this.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
                        window.location.href = `delete_patient.php?id=${patientId}`;
                    }
                });
            });
    </script>
</body>

</html>