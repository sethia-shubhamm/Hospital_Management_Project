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


$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'Doctors'");
if (mysqli_num_rows($tableCheck) == 0) {
    logError("Doctors table does not exist");
    
    $createDoctorsTable = "CREATE TABLE Doctors (
        DoctorID INT AUTO_INCREMENT PRIMARY KEY,
        LoginID INT,
        DoctorName VARCHAR(100) NOT NULL,
        Email VARCHAR(100),
        Phone VARCHAR(20),
        Specialty VARCHAR(50),
        Qualification VARCHAR(100),
        JoinDate DATE,
        Status VARCHAR(20) DEFAULT 'Active'
    )";

    if (!mysqli_query($conn, $createDoctorsTable)) {
        logError("Failed to create Doctors table: " . mysqli_error($conn));
    }
}


$doctorsQuery = "SELECT * FROM Doctors ORDER BY DoctorID DESC";
$doctorsResult = mysqli_query($conn, $doctorsQuery);

if (!$doctorsResult) {
    logError("Failed to fetch doctors: " . mysqli_error($conn));
}


$specialtiesQuery = "SELECT DISTINCT Specialty FROM Doctors WHERE Specialty IS NOT NULL AND Specialty != ''";
$specialtiesResult = mysqli_query($conn, $specialtiesQuery);
$specialties = [];

if ($specialtiesResult) {
    while ($row = mysqli_fetch_assoc($specialtiesResult)) {
        if (!empty($row['Specialty'])) {
            $specialties[] = $row['Specialty'];
        }
    }
} else {
    logError("Failed to fetch specialties: " . mysqli_error($conn));
}


if (empty($specialties)) {
    $specialties = [
        'Cardiology',
        'Neurology',
        'Orthopedics',
        'Pediatrics',
        'Oncology',
        'Dermatology',
        'General Medicine'
    ];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $doctorId = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $newStatus = mysqli_real_escape_string($conn, $_POST['status']);

    $updateQuery = "UPDATE Doctors SET Status = '$newStatus' WHERE DoctorID = '$doctorId'";
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
    <title>Doctors Management | Seattle Grace Hospital</title>
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

        .status-leave {
            color: orange;
        }

        .status-inactive {
            color: red;
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
                <li class="active">
                    <a href="index.php">
                        <i class="bi bi-person-badge"></i>
                        <span>Doctors</span>
                    </a>
                </li>
                <li>
                    <a href="../patients/index.php">
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
                    <i class="fas fa-sign-out-alt"></i>
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
                    <h1>Doctors Management</h1>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <input type="text" id="doctorSearch" placeholder="Search doctors...">
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
                    <h2>Manage Doctors</h2>
                    <button class="btn-primary" id="openAddDoctorModal"><i class="fas fa-plus"></i> Add New
                        Doctor</button>
                </div>

                <div class="filters-bar">
                    <div class="filter-group">
                        <label>Department:</label>
                        <select id="specialtyFilter">
                            <option value="">All Departments</option>
                            <?php foreach ($specialties as $specialty): ?>
                                <option value="<?php echo htmlspecialchars($specialty); ?>">
                                    <?php echo htmlspecialchars($specialty); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <button class="btn-filter" id="applyFilters">Apply Filters</button>
                </div>

                <div class="table-card doctors-table">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="select-all">
                                    </th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Specialization</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="doctorTableBody">
                                <?php if ($doctorsResult && mysqli_num_rows($doctorsResult) > 0): ?>
                                    <?php while ($doctor = mysqli_fetch_assoc($doctorsResult)): ?>
                                        <?php
                                        $initials = '';
                                        $nameParts = explode(' ', $doctor['DoctorName']);
                                        foreach ($nameParts as $part) {
                                            $initials .= strtoupper(substr($part, 0, 1));
                                        }
                                        $initials = substr($initials, 0, 2);

                                        $statusClass = 'available';
                                        if (isset($doctor['Status'])) {
                                            if ($doctor['Status'] == 'On Leave') {
                                                $statusClass = 'unavailable';
                                            } elseif ($doctor['Status'] == 'Inactive') {
                                                $statusClass = 'busy';
                                            }
                                        }
                                        ?>
                                        <tr data-specialty="<?php echo htmlspecialchars($doctor['Specialty'] ?? ''); ?>"
                                            data-status="<?php echo htmlspecialchars($doctor['Status'] ?? 'Active'); ?>">
                                            <td><input type="checkbox" class="select-row"></td>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar"><?php echo $initials; ?></div>
                                                    <div>
                                                        <span
                                                            class="user-name"><?php echo htmlspecialchars($doctor['DoctorName']); ?></span>
                                                        <span
                                                            class="user-email"><?php echo htmlspecialchars($doctor['Email'] ?? 'No email provided'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($doctor['Specialty'] ?? 'Not specified'); ?></td>
                                            <td><?php echo htmlspecialchars($doctor['Qualification'] ?? 'Not specified'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($doctor['Phone'] ?? 'Not provided'); ?></td>
                                            <td>
                                                <select class="status-dropdown <?php echo $statusClass; ?>"
                                                    data-doctor-id="<?php echo $doctor['DoctorID']; ?>">
                                                    <option value="Active" <?php echo (!isset($doctor['Status']) || $doctor['Status'] == 'Active') ? 'selected' : ''; ?>
                                                        class="status-active">Active</option>
                                                    <option value="On Leave" <?php echo (isset($doctor['Status']) && $doctor['Status'] == 'On Leave') ? 'selected' : ''; ?>
                                                        class="status-leave">On Leave</option>
                                                    <option value="Inactive" <?php echo (isset($doctor['Status']) && $doctor['Status'] == 'Inactive') ? 'selected' : ''; ?>
                                                        class="status-inactive">Inactive</option>
                                                </select>
                                            </td>
                                            <td class="actions">
                                                <button class="action-btn edit" data-id="<?php echo $doctor['DoctorID']; ?>"><i
                                                        class="fas fa-edit"></i></button>
                                                <a href="delete_doctor.php?id=<?php echo $doctor['DoctorID']; ?>"
                                                    class="action-btn delete"
                                                    onclick="return confirm('Are you sure you want to delete this doctor?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <button class="action-btn view" data-id="<?php echo $doctor['DoctorID']; ?>"><i
                                                        class="fas fa-eye"></i></button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">No doctors found. Add your first doctor!
                                        </td>
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

    <!-- Add Doctor Modal -->
    <div id="addDoctorModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeModal">&times;</span>
            <div class="modal-header">
                <h3>Add New Doctor</h3>
            </div>
            <form id="addDoctorForm" action="process_doctor.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="doctorName" class="form-label required-field">Doctor Name</label>
                        <input type="text" id="doctorName" name="doctorName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label required-field">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="specialty" class="form-label">Specialty/Department</label>
                        <select id="specialty" name="specialty" class="form-control">
                            <option value="">Select Specialty</option>
                            <?php foreach ($specialties as $specialty): ?>
                                <option value="<?php echo htmlspecialchars($specialty); ?>">
                                    <?php echo htmlspecialchars($specialty); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="qualification" class="form-label">Qualification</label>
                        <input type="text" id="qualification" name="qualification" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label required-field">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Active" selected>Active</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="cancelAdd">Cancel</button>
                    <button type="submit" class="btn-primary">Add Doctor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div id="editDoctorModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeEditModal">&times;</span>
            <div class="modal-header">
                <h3>Edit Doctor</h3>
            </div>
            <form id="editDoctorForm" action="update_doctor.php" method="POST">
                <input type="hidden" id="editDoctorId" name="doctorId">
                <div class="form-row">
                    <div class="form-group">
                        <label for="editDoctorName" class="form-label required-field">Doctor Name</label>
                        <input type="text" id="editDoctorName" name="doctorName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editEmail" class="form-label required-field">Email</label>
                        <input type="email" id="editEmail" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editPhone" class="form-label">Phone Number</label>
                        <input type="tel" id="editPhone" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="editSpecialty" class="form-label">Specialty/Department</label>
                        <select id="editSpecialty" name="specialty" class="form-control">
                            <option value="">Select Specialty</option>
                            <?php foreach ($specialties as $specialty): ?>
                                <option value="<?php echo htmlspecialchars($specialty); ?>">
                                    <?php echo htmlspecialchars($specialty); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editQualification" class="form-label">Qualification</label>
                        <input type="text" id="editQualification" name="qualification" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="editStatus" class="form-label">Status</label>
                        <select id="editStatus" name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-container">
                            <input type="checkbox" id="updatePassword" name="updatePassword" value="1">
                            <span class="checkmark"></span>
                            Update Password
                        </label>
                    </div>
                </div>
                <div class="form-row password-row" style="display: none;">
                    <div class="form-group">
                        <label for="editPassword" class="form-label">New Password</label>
                        <input type="password" id="editPassword" name="password" class="form-control">
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="button" class="btn-secondary" id="cancelEdit">Cancel</button>
                    <button type="submit" class="btn-primary">Update Doctor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Doctor Modal -->
    <div id="viewDoctorModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeViewModal">&times;</span>
            <div class="modal-header">
                <h3>Doctor Details</h3>
            </div>
            <div id="doctorDetails" class="doctor-details-container">
                <!-- Doctor details will be loaded dynamically -->
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading doctor details...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script>
        
        const tooltips = document.querySelectorAll('.tooltip');
        tooltips.forEach(tooltip => {
            const tooltipText = tooltip.getAttribute('data-tooltip');
            const span = document.createElement('span');
            span.className = 'tooltiptext';
            span.textContent = tooltipText;
            tooltip.appendChild(span);
        });

        
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mainContent = document.getElementById('mainContent');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                    mainContent.classList.toggle('expanded');
                });
            }

            
            function checkScreen() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    mainContent.classList.add('expanded');
                } else {
                    sidebar.classList.add('active');
                    mainContent.classList.remove('expanded');
                }
            }

            
            checkScreen();

            
            window.addEventListener('resize', checkScreen);
        });

        
        const addModal = document.getElementById('addDoctorModal');
        const addBtn = document.getElementById('openAddDoctorModal');
        const closeAddModal = document.getElementById('closeModal');
        const cancelAdd = document.getElementById('cancelAdd');

        if (addBtn) {
            addBtn.onclick = function () {
                addModal.style.display = "block";
            }
        }

        if (closeAddModal) {
            closeAddModal.onclick = function () {
                addModal.style.display = "none";
            }
        }

        if (cancelAdd) {
            cancelAdd.onclick = function () {
                addModal.style.display = "none";
            }
        }

        
        const editModal = document.getElementById('editDoctorModal');
        const closeEditModal = document.getElementById('closeEditModal');
        const cancelEdit = document.getElementById('cancelEdit');
        const editBtns = document.querySelectorAll('.edit');
        const updatePasswordCheckbox = document.getElementById('updatePassword');
        const passwordRow = document.querySelector('.password-row');

        if (updatePasswordCheckbox) {
            updatePasswordCheckbox.addEventListener('change', function () {
                if (this.checked) {
                    passwordRow.style.display = 'flex';
                } else {
                    passwordRow.style.display = 'none';
                }
            });
        }

        editBtns.forEach(btn => {
            btn.onclick = function () {
                const doctorId = this.getAttribute('data-doctor-id');
                fetchDoctorDetails(doctorId, 'edit');
            }
        });

        if (closeEditModal) {
            closeEditModal.onclick = function () {
                editModal.style.display = "none";
            }
        }

        if (cancelEdit) {
            cancelEdit.onclick = function () {
                editModal.style.display = "none";
            }
        }

        
        const viewModal = document.getElementById('viewDoctorModal');
        const closeViewModal = document.getElementById('closeViewModal');
        const viewBtns = document.querySelectorAll('.view');

        viewBtns.forEach(btn => {
            btn.onclick = function () {
                const doctorId = this.getAttribute('data-doctor-id');
                fetchDoctorDetails(doctorId, 'view');
            }
        });

        if (closeViewModal) {
            closeViewModal.onclick = function () {
                viewModal.style.display = "none";
            }
        }

        
        window.onclick = function (event) {
            if (event.target == addModal) {
                addModal.style.display = "none";
            }
            if (event.target == editModal) {
                editModal.style.display = "none";
            }
            if (event.target == viewModal) {
                viewModal.style.display = "none";
            }
        }

        
        function fetchDoctorDetails(doctorId, mode) {
            fetch(`get_doctor_details.php?doctorId=${doctorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (mode === 'edit') {
                            populateEditForm(data.doctor);
                        } else {
                            populateViewModal(data.doctor);
                        }
                    } else {
                        alert(data.message || 'Error fetching doctor details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching doctor details');
                });
        }

        
        function populateEditForm(doctor) {
            document.getElementById('editDoctorId').value = doctor.DoctorID;
            document.getElementById('editDoctorName').value = doctor.DoctorName;
            document.getElementById('editEmail').value = doctor.Email;
            document.getElementById('editPhone').value = doctor.PhoneNumber || '';
            document.getElementById('editSpecialty').value = doctor.Specialty || '';
            document.getElementById('editQualification').value = doctor.Qualification || '';
            document.getElementById('editStatus').value = doctor.Status || 'Active';

            
            document.getElementById('updatePassword').checked = false;
            document.querySelector('.password-row').style.display = 'none';

            
            editModal.style.display = "block";
        }

        
        function populateViewModal(doctor) {
            const detailsContainer = document.getElementById('doctorDetails');

            
            detailsContainer.innerHTML = '';

            
            const detailsHTML = `
                <div class="detail-row">
                    <div class="detail-label">Doctor ID:</div>
                    <div class="detail-value">${doctor.DoctorID}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Name:</div>
                    <div class="detail-value">${doctor.DoctorName}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value">${doctor.Email}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Phone Number:</div>
                    <div class="detail-value">${doctor.PhoneNumber || 'Not provided'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Specialty:</div>
                    <div class="detail-value">${doctor.Specialty || 'Not specified'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Qualification:</div>
                    <div class="detail-value">${doctor.Qualification || 'Not specified'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="status-badge ${doctor.Status.toLowerCase().replace(' ', '-')}">${doctor.Status}</span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Joined Date:</div>
                    <div class="detail-value">${doctor.JoinDate || 'Not available'}</div>
                </div>
            `;

            detailsContainer.innerHTML = detailsHTML;

            
            viewModal.style.display = "block";
        }

        
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const specialty = document.getElementById('filterSpecialty').value;
                const status = document.getElementById('filterStatus').value;

                window.location.href = `index.php?specialty=${encodeURIComponent(specialty)}&status=${encodeURIComponent(status)}`;
            });
        }

        
        const statusBtns = document.querySelectorAll('.status-badge');
        statusBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const doctorId = this.getAttribute('data-doctor-id');
                const currentStatus = this.textContent.trim();

                let newStatus;
                if (currentStatus === 'Active') {
                    newStatus = 'On Leave';
                } else if (currentStatus === 'On Leave') {
                    newStatus = 'Inactive';
                } else {
                    newStatus = 'Active';
                }

                if (confirm(`Change doctor status from ${currentStatus} to ${newStatus}?`)) {
                    updateDoctorStatus(doctorId, newStatus);
                }
            });
        });

        function updateDoctorStatus(doctorId, newStatus) {
            const formData = new FormData();
            formData.append('doctorId', doctorId);
            formData.append('status', newStatus);

            fetch('update_status.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Status updated successfully');
                        location.reload();
                    } else {
                        alert(data.message || 'Error updating status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the status');
                });
        }
    </script>
</body>

</html>