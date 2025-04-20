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
$patientID = $login_id;


$bill_id = isset($_GET['bill_id']) ? $_GET['bill_id'] : null;


$selectedBill = null;
$outstanding_bills = array();
$paymentSuccess = false;
$errorMsg = '';



$check_status_column = "SHOW COLUMNS FROM Bills LIKE 'BillStatus'";
$status_result = mysqli_query($conn, $check_status_column);
$has_bill_status = mysqli_num_rows($status_result) > 0;


$check_description_column = "SHOW COLUMNS FROM Bills LIKE 'Description'";
$description_result = mysqli_query($conn, $check_description_column);
$has_description = mysqli_num_rows($description_result) > 0;


$description_column = $has_description ? 'Description' : '';
if (!$has_description) {
    $alternate_columns = ['BillDescription', 'Details', 'ServiceDescription', 'Notes'];
    foreach ($alternate_columns as $col_name) {
        $check_alt_column = "SHOW COLUMNS FROM Bills LIKE '$col_name'";
        $alt_result = mysqli_query($conn, $check_alt_column);
        if (mysqli_num_rows($alt_result) > 0) {
            $description_column = $col_name;
            break;
        }
    }
}


if ($has_bill_status) {
    $bills_query = "SELECT * FROM Bills WHERE PatientID = '$patientID' AND BillStatus = 'Outstanding' ORDER BY BillDate ASC";
} else {
    
    $check_amountpaid_column = "SHOW COLUMNS FROM Payments LIKE 'AmountPaid'";
    $amountpaid_result = mysqli_query($conn, $check_amountpaid_column);
    $has_amountpaid = mysqli_num_rows($amountpaid_result) > 0;

    $payment_amount_column = $has_amountpaid ? 'AmountPaid' : 'PaymentAmount';

    $bills_query = "SELECT b.*, 
                  (SELECT SUM($payment_amount_column) FROM Payments WHERE BillID = b.BillID) as PaidAmount 
                  FROM Bills b 
                  WHERE b.PatientID = '$patientID' ORDER BY BillDate ASC";
}

$bills_result = mysqli_query($conn, $bills_query);

if ($bills_result && mysqli_num_rows($bills_result) > 0) {
    while ($bill = mysqli_fetch_assoc($bills_result)) {
        
        $is_outstanding = true;
        if ($has_bill_status) {
            $is_outstanding = $bill['BillStatus'] == 'Outstanding';
        } else {
            $paid_amount = isset($bill['PaidAmount']) ? $bill['PaidAmount'] : 0;
            $is_outstanding = $paid_amount < $bill['BillAmount'];
        }

        if ($is_outstanding) {
            $outstanding_bills[] = $bill;

            
            if ($bill_id && $bill['BillID'] == $bill_id) {
                $selectedBill = $bill;
            }
        }
    }
}


if (!$selectedBill && !empty($outstanding_bills)) {
    $selectedBill = $outstanding_bills[0];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $selected_bill_id = isset($_POST['bill_id']) ? $_POST['bill_id'] : null;
    $payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Credit Card';
    $card_number = isset($_POST['card_number']) ? $_POST['card_number'] : '';
    $card_holder = isset($_POST['card_holder']) ? $_POST['card_holder'] : '';
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';

    
    if (!$selected_bill_id) {
        $errorMsg = 'Please select a bill to pay.';
    } elseif ($payment_amount <= 0) {
        $errorMsg = 'Payment amount must be greater than zero.';
    } elseif ($payment_method == 'Credit Card' && (empty($card_number) || empty($card_holder) || empty($expiry_date) || empty($cvv))) {
        $errorMsg = 'Please fill in all credit card details.';
    } else {
        
        $bill_to_pay = null;
        foreach ($outstanding_bills as $bill) {
            if ($bill['BillID'] == $selected_bill_id) {
                $bill_to_pay = $bill;
                break;
            }
        }

        if (!$bill_to_pay) {
            $errorMsg = 'Selected bill could not be found.';
        } elseif ($payment_amount > $bill_to_pay['BillAmount']) {
            $errorMsg = 'Payment amount cannot exceed the bill amount.';
        } else {
            
            try {
                
                mysqli_begin_transaction($conn);

                
                $payment_date = date('Y-m-d');
                $payment_columns = array();
                $column_check_query = "SHOW COLUMNS FROM Payments";
                $column_check_result = mysqli_query($conn, $column_check_query);

                while ($column = mysqli_fetch_assoc($column_check_result)) {
                    $payment_columns[] = $column['Field'];
                }

                
                $sql_columns = array('BillID');
                $sql_values = array("'$selected_bill_id'");

                
                if (in_array('PatientID', $payment_columns)) {
                    $sql_columns[] = 'PatientID';
                    $sql_values[] = "'$patientID'";
                }

                
                if (in_array('Amount', $payment_columns)) {
                    $sql_columns[] = 'Amount';
                    $sql_values[] = "'$payment_amount'";
                } else if (in_array('AmountPaid', $payment_columns)) {
                    $sql_columns[] = 'AmountPaid';
                    $sql_values[] = "'$payment_amount'";
                } else {
                    
                    $sql_columns[] = 'AmountPaid';
                    $sql_values[] = "'$payment_amount'";
                }

                
                if (in_array('PaymentDate', $payment_columns)) {
                    $sql_columns[] = 'PaymentDate';
                    $sql_values[] = "'$payment_date'";
                }

                
                if (in_array('PaymentMethod', $payment_columns)) {
                    $sql_columns[] = 'PaymentMethod';
                    $sql_values[] = "'$payment_method'";
                }

                
                $columns_str = implode(', ', $sql_columns);
                $values_str = implode(', ', $sql_values);
                $payment_query = "INSERT INTO Payments ($columns_str) VALUES ($values_str)";

                $payment_result = mysqli_query($conn, $payment_query);

                if (!$payment_result) {
                    throw new Exception("Payment insertion failed: " . mysqli_error($conn));
                }

                
                if ($has_bill_status && $payment_amount >= $bill_to_pay['BillAmount']) {
                    $update_bill_query = "UPDATE Bills SET BillStatus = 'Paid' WHERE BillID = '$selected_bill_id'";
                    $update_result = mysqli_query($conn, $update_bill_query);

                    if (!$update_result) {
                        throw new Exception("Bill status update failed: " . mysqli_error($conn));
                    }
                }

                
                mysqli_commit($conn);
                $paymentSuccess = true;

                
                header("Location: index.php?payment=success");
                exit();

            } catch (Exception $e) {
                
                mysqli_rollback($conn);
                $errorMsg = "Payment processing error: " . $e->getMessage();
                logError($errorMsg);
            }
        }
    }
}


function formatCurrency($amount)
{
    return '$' . number_format($amount, 2);
}
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
                <img src="../dashboard/icons/logo.png" alt="Logo"> <span
                    style="font-weight:bold;color:#3498db;">Hospital Management System</span> <span
                    style="font-weight:bold;color:#3498db;">Hospital Management System</span>
                <h6>Hospital Management System</h6>
            </div>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="../../index.php">HOME</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../allDoctors/index.html">ALL DOCTORS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../bloodBank/index.html">BLOOD BANK</a>
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
                    <h1>Make a Payment</h1>
                    <p><a href="index.php" class="btn btn-outline-primary btn-sm">← Back to Bills & Payments</a></p>
                </div>

                <?php if (empty($outstanding_bills)): ?>
                    <div class="alert alert-info">
                        <h4>No outstanding bills</h4>
                        <p>You have no outstanding bills at this time.</p>
                        <a href="index.php" class="btn btn-primary">Back to Bills & Payments</a>
                    </div>
                <?php elseif ($paymentSuccess): ?>
                    <div class="alert alert-success">
                        <h4>Payment Successful!</h4>
                        <p>Your payment has been processed successfully.</p>
                        <a href="index.php" class="btn btn-primary">View All Bills</a>
                    </div>
                <?php else: ?>
                    <?php if (!empty($errorMsg)): ?>
                        <div class="alert alert-danger">
                            <?php echo $errorMsg; ?>
                        </div>
                    <?php endif; ?>

                    <div class="payment-form-container">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Payment Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                            <div class="mb-3">
                                                <label for="bill_id" class="form-label">Select Bill</label>
                                                <select class="form-select" id="bill_id" name="bill_id" required>
                                                    <?php foreach ($outstanding_bills as $bill): ?>
                                                        <option value="<?php echo $bill['BillID']; ?>" <?php echo ($selectedBill && $selectedBill['BillID'] == $bill['BillID']) ? 'selected' : ''; ?>>
                                                            Bill #<?php echo $bill['BillID']; ?> -
                                                            <?php
                                                            if (!empty($description_column) && isset($bill[$description_column])) {
                                                                echo htmlspecialchars($bill[$description_column]);
                                                            } else {
                                                                echo "Medical Service";
                                                            }
                                                            ?>
                                                            (<?php echo formatCurrency($bill['BillAmount']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="payment_amount" class="form-label">Payment Amount</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" class="form-control"
                                                        id="payment_amount" name="payment_amount"
                                                        value="<?php echo $selectedBill ? $selectedBill['BillAmount'] : ''; ?>"
                                                        required>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="payment_method" class="form-label">Payment Method</label>
                                                <select class="form-select" id="payment_method" name="payment_method"
                                                    required>
                                                    <option value="Credit Card">Credit Card</option>
                                                    <option value="Debit Card">Debit Card</option>
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                </select>
                                            </div>

                                            <div id="creditCardFields">
                                                <div class="mb-3">
                                                    <label for="card_number" class="form-label">Card Number</label>
                                                    <input type="text" class="form-control" id="card_number"
                                                        name="card_number" placeholder="1234 5678 9012 3456">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="card_holder" class="form-label">Card Holder Name</label>
                                                    <input type="text" class="form-control" id="card_holder"
                                                        name="card_holder">
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="expiry_date" class="form-label">Expiry Date</label>
                                                        <input type="text" class="form-control" id="expiry_date"
                                                            name="expiry_date" placeholder="MM/YY">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="cvv" class="form-label">CVV</label>
                                                        <input type="text" class="form-control" id="cvv" name="cvv"
                                                            placeholder="123">
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="submit" name="submit_payment" class="btn btn-primary">Process
                                                Payment</button>
                                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Bill Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($selectedBill): ?>
                                            <div class="bill-details">
                                                <div class="bill-row">
                                                    <span>Bill ID:</span>
                                                    <span>#<?php echo $selectedBill['BillID']; ?></span>
                                                </div>
                                                <div class="bill-row">
                                                    <span>Description:</span>
                                                    <span>
                                                        <?php
                                                        if (!empty($description_column) && isset($selectedBill[$description_column])) {
                                                            echo htmlspecialchars($selectedBill[$description_column]);
                                                        } else {
                                                            echo "Medical Service";
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="bill-row">
                                                    <span>Bill Date:</span>
                                                    <span><?php echo date('M d, Y', strtotime($selectedBill['BillDate'])); ?></span>
                                                </div>
                                                <?php if (isset($selectedBill['DueDate'])): ?>
                                                    <div class="bill-row">
                                                        <span>Due Date:</span>
                                                        <span><?php echo date('M d, Y', strtotime($selectedBill['DueDate'])); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="bill-row total">
                                                    <span>Total Amount:</span>
                                                    <span><?php echo formatCurrency($selectedBill['BillAmount']); ?></span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <p>No bill selected.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .payment-form-container {
            margin-top: 20px;
        }

        .card {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: none;
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f5f5f5;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        .bill-details {
            font-size: 14px;
        }

        .bill-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .bill-row.total {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #eee;
            border-bottom: none;
            font-weight: 600;
            font-size: 16px;
        }

       
        .content-area {
            margin-top: -37px;
        }

        .welcome-section {
            margin-top: 10px;
        }
    </style>

    <script>
        
        document.addEventListener('DOMContentLoaded', function () {
            const paymentMethodSelect = document.getElementById('payment_method');
            const creditCardFields = document.getElementById('creditCardFields');
            const cardFields = ['card_number', 'card_holder', 'expiry_date', 'cvv'];

            if (paymentMethodSelect && creditCardFields) {
                paymentMethodSelect.addEventListener('change', function () {
                    const isCreditCard = this.value === 'Credit Card' || this.value === 'Debit Card';
                    creditCardFields.style.display = isCreditCard ? 'block' : 'none';

                    
                    cardFields.forEach(field => {
                        const element = document.getElementById(field);
                        if (element) {
                            element.required = isCreditCard;
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>