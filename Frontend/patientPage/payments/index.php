<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'Patient') {
    $_SESSION['login_error'] = "Please log in as a patient to access this page";
    header("Location: ../../login/index.php");
    exit();
}


require_once '../../../db_connect.php';


function logError($message)
{
    $logFile = "../error_log.txt";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


$login_id = $_SESSION['user_id'];
$email = $_SESSION['email'];


$patient_check_query = "SELECT * FROM Patients WHERE PatientID = '$login_id'";
$patient_result = mysqli_query($conn, $patient_check_query);

if (!$patient_result) {
    logError("Patient check query failed: " . mysqli_error($conn));
    $patientID = $login_id;
    $patientName = "Patient User";
} else if (mysqli_num_rows($patient_result) > 0) {
    $patient = mysqli_fetch_assoc($patient_result);
    $patientID = $patient['PatientID'];
    $patientName = $patient['PatientName'];
} else {
    
    $user_query = "SELECT * FROM LoginCredentials WHERE LoginID = '$login_id'";
    $user_result = mysqli_query($conn, $user_query);

    if (!$user_result) {
        logError("User query failed: " . mysqli_error($conn));
        $patientID = $login_id;
        $patientName = "Patient User";
    } else {
        $user = mysqli_fetch_assoc($user_result);

        
        $create_patient_query = "INSERT INTO Patients (PatientID, PatientName, PatientAge, PatientGender, BloodType, ContactInfo) 
                            VALUES ('$login_id', 'Patient User', NULL, NULL, NULL, NULL)";
        $create_result = mysqli_query($conn, $create_patient_query);

        if (!$create_result) {
            logError("Patient creation failed: " . mysqli_error($conn));
        }

        
        $patient_result = mysqli_query($conn, $patient_check_query);
        if (!$patient_result) {
            logError("Patient re-fetch failed: " . mysqli_error($conn));
            $patientID = $login_id;
            $patientName = "Patient User";
        } else {
            $patient = mysqli_fetch_assoc($patient_result);
            $patientID = $patient['PatientID'];
            $patientName = $patient['PatientName'];
        }
    }
}


$paymentsTableCheck = "SHOW TABLES LIKE 'Payments'";
$paymentsTableExists = mysqli_query($conn, $paymentsTableCheck);
$paymentHistory = array();

if (mysqli_num_rows($paymentsTableExists) > 0) {
    
    $columnsQuery = "SHOW COLUMNS FROM Payments";
    $columnsResult = mysqli_query($conn, $columnsQuery);
    $paymentPatientIdCol = 'PatientID'; 
    $billIdCol = 'BillID';
    $paymentAmountCol = 'Amount';
    $paymentDateCol = 'PaymentDate';

    if ($columnsResult) {
        $hasPatientId = false;
        $columns = array();
        while ($column = mysqli_fetch_assoc($columnsResult)) {
            $colName = $column['Field'];
            $columns[] = $colName;

            
            if (strtolower($colName) === 'patientid' || strtolower($colName) === 'patient_id') {
                $paymentPatientIdCol = $colName;
                $hasPatientId = true;
            }

            
            if (strtolower($colName) === 'billid' || strtolower($colName) === 'bill_id') {
                $billIdCol = $colName;
            }

            
            if (strtolower($colName) === 'amount' || strtolower($colName) === 'paymentamount') {
                $paymentAmountCol = $colName;
            } else if (strtolower($colName) === 'amountpaid') {
                $paymentAmountCol = 'AmountPaid';
            }

            
            if (strtolower($colName) === 'paymentdate' || strtolower($colName) === 'date') {
                $paymentDateCol = $colName;
            }
        }

        
        if (!$hasPatientId) {
            
            $paymentHistoryQuery = "SELECT p.*, 
                            p.$paymentAmountCol as PaymentAmount,
                            b.PatientID, b.BillAmount, b.BillDate 
                            FROM Payments p 
                            JOIN Bills b ON p.$billIdCol = b.BillID 
                            WHERE b.PatientID = '$patientID' 
                            ORDER BY p.$paymentDateCol DESC";
        } else {
            
            $paymentHistoryQuery = "SELECT p.*, 
                            p.$paymentAmountCol as PaymentAmount,
                            b.BillAmount, b.BillDate 
                            FROM Payments p 
                            LEFT JOIN Bills b ON p.$billIdCol = b.BillID 
                            WHERE p.$paymentPatientIdCol = '$patientID' 
                            ORDER BY p.$paymentDateCol DESC";
        }

        $paymentHistoryResult = mysqli_query($conn, $paymentHistoryQuery);

        if ($paymentHistoryResult && mysqli_num_rows($paymentHistoryResult) > 0) {
            while ($payment = mysqli_fetch_assoc($paymentHistoryResult)) {
                $paymentHistory[] = $payment;
            }
        }

        
        $check_amountpaid_column = "SHOW COLUMNS FROM Payments LIKE 'AmountPaid'";
        $amountpaid_result = mysqli_query($conn, $check_amountpaid_column);
        $has_amountpaid = mysqli_num_rows($amountpaid_result) > 0;

        if ($has_amountpaid) {
            $paymentAmountCol = 'AmountPaid';
        }
    }
}


$tableCheckQuery = "SHOW TABLES LIKE 'Bills'";
$tableExists = mysqli_query($conn, $tableCheckQuery);
$outstandingBills = array();
$paidBills = array();
$total_outstanding = 0;

if (mysqli_num_rows($tableExists) > 0) {
    
    $check_status_column = "SHOW COLUMNS FROM Bills LIKE 'BillStatus'";
    $status_result = mysqli_query($conn, $check_status_column);
    $has_bill_status = mysqli_num_rows($status_result) > 0;

    
    $check_duedate_column = "SHOW COLUMNS FROM Bills LIKE 'DueDate'";
    $duedate_result = mysqli_query($conn, $check_duedate_column);
    $has_duedate = mysqli_num_rows($duedate_result) > 0;

    
    $check_description_column = "SHOW COLUMNS FROM Bills LIKE 'Description'";
    $description_result = mysqli_query($conn, $check_description_column);
    $has_description = mysqli_num_rows($description_result) > 0;

    
    $alternate_description_column = '';
    if (!$has_description) {
        $alternate_columns = ['BillDescription', 'Details', 'ServiceDescription', 'Notes'];
        foreach ($alternate_columns as $col_name) {
            $check_alt_column = "SHOW COLUMNS FROM Bills LIKE '$col_name'";
            $alt_result = mysqli_query($conn, $check_alt_column);
            if (mysqli_num_rows($alt_result) > 0) {
                $alternate_description_column = $col_name;
                break;
            }
        }
    }

    
    $bills_query = "SELECT * FROM Bills WHERE PatientID = '$patientID' ORDER BY ";
    $bills_query .= $has_duedate ? "DueDate ASC" : "BillDate ASC";

    $bills_result = mysqli_query($conn, $bills_query);

    if ($bills_result && mysqli_num_rows($bills_result) > 0) {
        while ($bill = mysqli_fetch_assoc($bills_result)) {
            
            $is_outstanding = true;

            if ($has_bill_status) {
                $is_outstanding = $bill['BillStatus'] == 'Outstanding';
            } else {
                
                $payment_check_query = "SELECT SUM($paymentAmountCol) as PaidAmount FROM Payments WHERE BillID = '{$bill['BillID']}'";
                $payment_check_result = mysqli_query($conn, $payment_check_query);

                if ($payment_check_result && $payment_row = mysqli_fetch_assoc($payment_check_result)) {
                    $paid_amount = $payment_row['PaidAmount'] ?: 0;
                    $is_outstanding = $paid_amount < $bill['BillAmount'];
                }
            }

            
            if ($is_outstanding) {
                $outstandingBills[] = $bill;
                $total_outstanding += $bill['BillAmount'];
            } else {
                $paidBills[] = $bill;
            }
        }
    }
}


function formatDate($dateString)
{
    if (!$dateString)
        return '';
    return date('l, d F Y', strtotime($dateString));
}


function formatCurrency($amount)
{
    if (!$amount)
        return '$0.00';
    return '$' . number_format($amount, 2);
}


$today = formatDate(date('Y-m-d'));
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, width=device-width">

    <link rel="stylesheet" href="../dashboard/style.css" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
</head>

<body>
    <div class="desktop">
        <div class="navbar">
            <div class="logo">
                <img src="../dashboard/icons/logo.png" alt="Logo">
                <h6>Seattle Grace Hospital</h6>
            </div>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="../../index.php">HOME</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../allDoctors/index.php">ALL DOCTORS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../bloodBank/index.php">BLOOD BANK</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../index.php#contact">CONTACT</a>
                </li>
            </ul>
        </div>

        <div class="mainContainer">
            <div class="choiceSection">
                <div class="menu-items">
                    <div>
                        <img src="../dashboard/icons/dashboard.png" alt="">
                        <a href="../dashboard/index.php" style="text-decoration: none;">
                            <h6>Dashboard</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/profile.png" alt="">
                        <a href="../profile/index.php" style="text-decoration: none;">
                            <h6>Profile</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/appointment.png" alt="">
                        <a href="../appointment/index.php" style="text-decoration: none;">
                            <h6>Appointment</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/records.png" alt="">
                        <a href="../records/index.php" style="text-decoration: none;">
                            <h6>Records</h6>
                        </a>
                    </div>
                    <div class="active">
                        <img src="../dashboard/icons/invoice.png" alt="">
                        <a href="index.php" style="text-decoration: none;">
                            <h6>Payments</h6>
                        </a>
                    </div>
                    <div>
                        <img src="../dashboard/icons/insurance.png" alt="" style="height: 32.25px;">
                        <a href="../insurance/index.php" style="text-decoration: none;">
                            <h6>Insurance</h6>
                        </a>
                    </div>
                </div>
                <div class="logout">
                    <img src="../dashboard/icons/Logout.png" alt="">
                    <a href="../../logout.php" style="text-decoration: none; color: inherit;">
                        <h6>Logout</h6>
                    </a>
                </div>
            </div>

            <div class="content-area">
                <div class="welcome-section">
                    <h1>Bills & Payments</h1>
                    <p id="currentDate"><?php echo $today; ?></p>
                </div>

                <div class="info-grid payment-grid">
                    <!-- Outstanding Bills Section -->
                    <div class="info-card bills-card">
                        <h3>Outstanding Bills</h3>
                        <div class="bill-content">
                            <?php if (!empty($outstandingBills)): ?>
                                <div class="bills-table">
                                    <table class="table table-responsive table-hover">
                                        <thead>
                                            <tr>
                                                <th>Bill ID</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Due Date</th>
                                                <th>Amount</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($outstandingBills as $bill): ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($bill['BillID']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($bill['BillDate'])); ?></td>
                                                    <td>
                                                        <?php
                                                        if (isset($bill['Description'])) {
                                                            echo htmlspecialchars($bill['Description']);
                                                        } elseif (!empty($alternate_description_column) && isset($bill[$alternate_description_column])) {
                                                            echo htmlspecialchars($bill[$alternate_description_column]);
                                                        } else {
                                                            echo "Medical Service"; 
                                                        }
                                                        ?>
                                                    </td>
                                                    <td
                                                        class="<?php echo (isset($bill['DueDate']) && strtotime($bill['DueDate']) < time()) ? 'text-danger' : ''; ?>">
                                                        <?php
                                                        if (isset($bill['DueDate'])) {
                                                            echo date('M d, Y', strtotime($bill['DueDate']));
                                                        } else {
                                                            
                                                            $assumed_due_date = date('Y-m-d', strtotime($bill['BillDate'] . ' + 30 days'));
                                                            echo date('M d, Y', strtotime($assumed_due_date));
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo formatCurrency($bill['BillAmount']); ?></td>
                                                    <td>
                                                        <a href="payment_process_new.php?bill_id=<?php echo $bill['BillID']; ?>"
                                                            class="btn btn-primary btn-sm">Pay Now</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                    <div class="total-row">
                                        <span>Total Outstanding:</span>
                                        <span class="total-amount"><?php echo formatCurrency($total_outstanding); ?></span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <p>No pending bills found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Payment History Section -->
                    <div class="info-card payment-history-card">
                        <h3>Payment History</h3>
                        <div class="payment-history-content">
                            <?php if (!empty($paymentHistory)): ?>
                                <div class="payment-table">
                                    <table class="table table-responsive table-hover">
                                        <thead>
                                            <tr>
                                                <th>Payment ID</th>
                                                <th>Date</th>
                                                <th>Bill Ref</th>
                                                <th>Method</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paymentHistory as $payment): ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($payment['PaymentID']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($payment[$paymentDateCol])); ?></td>
                                                    <td>
                                                        <?php if (isset($payment[$billIdCol])): ?>
                                                            #<?php echo htmlspecialchars($payment[$billIdCol]); ?>
                                                        <?php else: ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($payment['PaymentMethod'] ?? 'Credit Card'); ?>
                                                    </td>
                                                    <td><?php
                                                    
                                                    $amount = $payment['PaymentAmount'] ?? 0;
                                                    echo formatCurrency($amount);
                                                    ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <p>No payment history available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Paid Bills Section -->
                    <div class="info-card paid-bills-card">
                        <h3>Paid Bills</h3>
                        <div class="paid-bills-content">
                            <?php if (!empty($paidBills)): ?>
                                <div class="bills-table">
                                    <table class="table table-responsive table-hover">
                                        <thead>
                                            <tr>
                                                <th>Bill ID</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paidBills as $bill): ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($bill['BillID']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($bill['BillDate'])); ?></td>
                                                    <td>
                                                        <?php
                                                        if (isset($bill['Description'])) {
                                                            echo htmlspecialchars($bill['Description']);
                                                        } elseif (!empty($alternate_description_column) && isset($bill[$alternate_description_column])) {
                                                            echo htmlspecialchars($bill[$alternate_description_column]);
                                                        } else {
                                                            echo "Medical Service"; 
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo formatCurrency($bill['BillAmount']); ?></td>
                                                    <td><span class="badge bg-success">Paid</span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <p>No paid bills found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bills-table {
            width: 100%;
            overflow-x: auto;
        }

        .bills-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .bills-table th {
            background-color: #f5f5f5;
            padding: 8px 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #ddd;
        }

        .bills-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }

        .bills-table tr:hover {
            background-color: #f9f9f9;
        }

        .text-danger {
            color: #d63031;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #eee;
            font-weight: 600;
        }

        .total-amount {
            color: #d63031;
            font-size: 16px;
        }

        .payment-grid {
            grid-template-columns: repeat(1, 1fr);
        }

        .bills-card,
        .payment-history-card,
        .paid-bills-card {
            grid-column: span 1;
        }

        .no-data {
            text-align: center;
            padding: 30px 20px;
            color: #777;
            font-style: italic;
        }

       
        .content-area {
            margin-top: -37px;
        }

        .welcome-section {
            margin-top: 10px;
        }

        @media (min-width: 992px) {
            .payment-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .bills-card {
                grid-column: span 2;
            }
        }
    </style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>