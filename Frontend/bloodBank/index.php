<?php
session_start();


require_once '../../db_connect.php';


function logError($message)
{
    $logFile = 'error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


function getBloodInventory()
{
    global $conn;
    $inventory = [];

    $query = "SELECT * FROM BloodInventory";
    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $quantity = (int) $row['Quantity'];
            $status = 'available';

            if ($quantity <= 2) {
                $status = 'critical';
            } else if ($quantity <= 5) {
                $status = 'low';
            }

            $inventory[] = [
                'BLOOD_TYPE' => $row['BloodType'],
                'QUANTITY' => $quantity,
                'EXPIRY_DATE' => $row['ExpiryDate'],
                'status' => $status
            ];
        }
    } else {
        logError("Failed to fetch inventory: " . mysqli_error($conn));
    }

    return $inventory;
}


$bloodInventory = getBloodInventory();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, width=device-width">

    <link rel="stylesheet" href="style.css" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <title>Blood Bank | Seattle Grace Hospital</title>
</head>

<body>
    <div class="desktop">
        <div class="navbar">
            <div class="logo">
                <img src="images/logo.png" alt="">
                <h6 style="margin-top: 7.5px;">Seattle Grace Hospital</h6>
            </div>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="../index.php">HOME</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../allDoctors/index.php">ALL DOCTORS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">BLOOD BANK</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../index.php#contact">CONTACT</a>
                </li>
            </ul>
            <div class="login">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button type="button" class="btn btn-primary" id="viewProfileBtn"
                        onclick="window.location.href='<?php echo $_SESSION['user_type'] == 'Patient' ? '../patientPage/dashboard/index.php' : '../doctorPage/dashboard/index.php'; ?>'">
                        My Profile
                    </button>
                    <a href="../../logout.php"><button type="button" class="btn btn-outline-danger">Logout</button></a>
                <?php else: ?>
                    <a href="../../login/index.php"><button type="button" class="btn btn-primary">Login</button></a>
                    <a href="../../signUp/index.php"><button type="button" class="btn btn-primary">Sign Up</button></a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['donation_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show message-alert" role="alert">
                <?php echo $_SESSION['donation_success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['donation_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['donation_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show message-alert" role="alert">
                <?php echo $_SESSION['donation_error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['donation_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['request_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show message-alert" role="alert">
                <?php echo $_SESSION['request_success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['request_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['request_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show message-alert" role="alert">
                <?php echo $_SESSION['request_error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['request_error']); ?>
        <?php endif; ?>

        <div class="main-container">
            <div class="blood-bank-container">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="donate">Donate Blood</button>
                    <button class="tab-btn" data-tab="receive">Receive Blood</button>
                    <button class="tab-btn" data-tab="inventory">Blood Inventory</button>
                </div>

                <div class="tab-content">
                    <!-- Donate Blood Tab -->
                    <div id="donate" class="tab-pane active">
                        <div class="blood-header">
                            <div class="icon-container">
                                <i class="fas fa-hand-holding-medical"></i>
                            </div>
                            <h1>Donate Blood</h1>
                            <p>Your donation can save lives. Fill the form below to schedule a blood donation.</p>
                        </div>

                        <form class="blood-form" action="process_donation.php" method="post">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="donor-name">Full Name</label>
                                    <input type="text" id="donor-name" name="donor_name"
                                        placeholder="Enter your full name" required>
                                </div>
                                <div class="form-group">
                                    <label for="donor-age">Age</label>
                                    <input type="number" id="donor-age" name="donor_age" placeholder="Enter your age"
                                        required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="donor-email">Email</label>
                                    <input type="email" id="donor-email" name="donor_email"
                                        placeholder="Enter your email" required>
                                </div>
                                <div class="form-group">
                                    <label for="donor-phone">Phone Number</label>
                                    <input type="tel" id="donor-phone" name="donor_phone"
                                        placeholder="Enter your phone number" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="donor-blood-group">Blood Group</label>
                                    <select id="donor-blood-group" name="donor_blood_group" required>
                                        <option value="" disabled selected>Select your blood group</option>
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
                                <div class="form-group">
                                    <label for="donor-date">Preferred Date</label>
                                    <input type="date" id="donor-date" name="donor_date" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="donor-medical">Medical History (Optional)</label>
                                <textarea id="donor-medical" name="donor_medical"
                                    placeholder="Any relevant medical history"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary submit-btn">Schedule Donation</button>
                        </form>
                    </div>

                    <!-- Receive Blood Tab -->
                    <div id="receive" class="tab-pane">
                        <div class="blood-header">
                            <div class="icon-container">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <h1>Receive Blood</h1>
                            <p>Request blood for medical purposes. Complete the form to make a request.</p>
                        </div>

                        <form class="blood-form" action="process_request.php" method="post">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="patient-name">Patient Name</label>
                                    <input type="text" id="patient-name" name="patient_name"
                                        placeholder="Enter patient name" required>
                                </div>
                                <div class="form-group">
                                    <label for="hospital-name">Hospital Name</label>
                                    <input type="text" id="hospital-name" name="hospital_name"
                                        placeholder="Enter hospital name" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="requester-email">Contact Email</label>
                                    <input type="email" id="requester-email" name="requester_email"
                                        placeholder="Enter contact email" required>
                                </div>
                                <div class="form-group">
                                    <label for="requester-phone">Contact Phone</label>
                                    <input type="tel" id="requester-phone" name="requester_phone"
                                        placeholder="Enter contact phone" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="required-blood-group">Required Blood Group</label>
                                    <select id="required-blood-group" name="required_blood_group" required>
                                        <option value="" disabled selected>Select required blood group</option>
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
                                <div class="form-group">
                                    <label for="blood-units">Units Required</label>
                                    <input type="number" id="blood-units" name="blood_units"
                                        placeholder="Number of units needed" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="urgency">Urgency Level</label>
                                <select id="urgency" name="urgency" required>
                                    <option value="" disabled selected>Select urgency level</option>
                                    <option value="emergency">Emergency - Immediate</option>
                                    <option value="urgent">Urgent - Within 24 hours</option>
                                    <option value="scheduled">Scheduled - Specific date</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="request-reason">Medical Reason</label>
                                <textarea id="request-reason" name="request_reason"
                                    placeholder="Explain the medical need for blood" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary submit-btn">Submit Request</button>
                        </form>
                    </div>

                    <!-- Blood Inventory Tab -->
                    <div id="inventory" class="tab-pane">
                        <div class="blood-header">
                            <div class="icon-container">
                                <i class="fas fa-tint"></i>
                            </div>
                            <h1>Blood Inventory</h1>
                            <p>Current blood supply status at our blood bank.</p>
                        </div>

                        <div class="inventory-container">
                            <div class="inventory-filters">
                                <div class="filter-group">
                                    <label for="filter-blood-group">Filter by Blood Group</label>
                                    <select id="filter-blood-group">
                                        <option value="all">All Blood Groups</option>
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

                                <div class="filter-group">
                                    <label for="filter-status">Filter by Status</label>
                                    <select id="filter-status">
                                        <option value="all">All Status</option>
                                        <option value="available">Available</option>
                                        <option value="low">Low Stock</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                            </div>

                            <div class="inventory-table">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Blood Group</th>
                                            <th>Available Units</th>
                                            <th>Last Updated</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($bloodInventory)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No blood inventory data available</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($bloodInventory as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['BLOOD_TYPE']); ?></td>
                                                    <td><?php echo (int) $item['QUANTITY']; ?> units</td>
                                                    <td><?php echo date('M j, Y', strtotime($item['EXPIRY_DATE'])); ?></td>
                                                    <td><span
                                                            class="status <?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="blood-drives">
                                <h2>Upcoming Blood Drives</h2>
                                <div class="drives-container">
                                    <div class="drive-card">
                                        <div class="drive-date">
                                            <span class="day">15</span>
                                            <span class="month">APR</span>
                                        </div>
                                        <div class="drive-details">
                                            <h3>Community Blood Drive</h3>
                                            <p>Location: Main Hospital Campus</p>
                                            <p>Time: 9:00 AM - 4:00 PM</p>
                                        </div>
                                    </div>

                                    <div class="drive-card">
                                        <div class="drive-date">
                                            <span class="day">22</span>
                                            <span class="month">APR</span>
                                        </div>
                                        <div class="drive-details">
                                            <h3>University Blood Drive</h3>
                                            <p>Location: Student Center</p>
                                            <p>Time: 10:00 AM - 3:00 PM</p>
                                        </div>
                                    </div>

                                    <div class="drive-card">
                                        <div class="drive-date">
                                            <span class="day">05</span>
                                            <span class="month">MAY</span>
                                        </div>
                                        <div class="drive-details">
                                            <h3>Corporate Blood Drive</h3>
                                            <p>Location: Business Park</p>
                                            <p>Time: 11:00 AM - 5:00 PM</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');
            const donateForm = document.querySelector('#donate .blood-form');
            const receiveForm = document.querySelector('#receive .blood-form');
            const bloodGroupFilter = document.getElementById('filter-blood-group');
            const statusFilter = document.getElementById('filter-status');

            
            tabButtons.forEach(button => {
                button.addEventListener('click', function () {
                    
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));

                    
                    this.classList.add('active');

                    
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');

                    
                    if (tabId === 'inventory') {
                        fetchInventoryData();
                    }
                });
            });

            
            function fetchInventoryData() {
                const bloodGroup = bloodGroupFilter ? bloodGroupFilter.value : 'all';
                const status = statusFilter ? statusFilter.value : 'all';

                fetch(`get_inventory.php?blood_group=${bloodGroup}&status=${status}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateInventoryTable(data.data);
                        } else {
                            console.error('Error fetching inventory data:', data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            
            function updateInventoryTable(inventory) {
                const inventoryTable = document.querySelector('.inventory-table tbody');
                if (!inventoryTable) return;

                if (inventory.length === 0) {
                    inventoryTable.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center">No results match your filter criteria</td>
                    </tr>
                `;
                    return;
                }

                const tableRows = inventory.map(item => `
                <tr>
                    <td>${item.BLOOD_TYPE}</td>
                    <td>${item.QUANTITY} units</td>
                    <td>${formatDate(item.EXPIRY_DATE)}</td>
                    <td><span class="status ${item.status}">${item.status.charAt(0).toUpperCase() + item.status.slice(1)}</span></td>
                </tr>
            `).join('');

                inventoryTable.innerHTML = tableRows;
            }

            
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }

            
            if (bloodGroupFilter && statusFilter) {
                bloodGroupFilter.addEventListener('change', fetchInventoryData);
                statusFilter.addEventListener('change', fetchInventoryData);
            }

            
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });

            
            const donorDateInput = document.getElementById('donor-date');
            if (donorDateInput) {
                const today = new Date().toISOString().split('T')[0];
                donorDateInput.setAttribute('min', today);
                donorDateInput.value = today;
            }
        });
    </script>
</body>

</html>