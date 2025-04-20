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


$insuranceInfo = null;
$insuranceQuery = "SELECT * FROM Insurance WHERE PatientID = '$patientID'";
$insuranceResult = mysqli_query($conn, $insuranceQuery);

if ($insuranceResult && mysqli_num_rows($insuranceResult) > 0) {
    $insuranceInfo = mysqli_fetch_assoc($insuranceResult);
}


$claimableBills = array();
$billsQuery = "SELECT b.* FROM Bills b 
               LEFT JOIN Payments p ON b.BillID = p.BillID 
               WHERE b.PatientID = '$patientID' 
               AND (p.PaymentID IS NULL OR p.AmountPaid < b.BillAmount)
               ORDER BY b.BillDate DESC";
$billsResult = mysqli_query($conn, $billsQuery);

if ($billsResult && mysqli_num_rows($billsResult) > 0) {
    while ($bill = mysqli_fetch_assoc($billsResult)) {
        $claimableBills[] = $bill;
    }
}


$claimMessage = '';
$claimError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_claim'])) {
    $billID = $_POST['bill_id'] ?? '';
    $claimAmount = $_POST['claim_amount'] ?? '';
    $claimReason = $_POST['claim_reason'] ?? '';
    
    if (empty($billID) || empty($claimAmount) || empty($claimReason)) {
        $claimError = "All fields are required to submit a claim";
    } else if (!$insuranceInfo) {
        $claimError = "You need to add insurance information before submitting a claim";
    } else {
        
        $billCheckQuery = "SELECT * FROM Bills WHERE BillID = '$billID' AND PatientID = '$patientID'";
        $billCheckResult = mysqli_query($conn, $billCheckQuery);
        
        if (!$billCheckResult || mysqli_num_rows($billCheckResult) === 0) {
            $claimError = "Invalid bill selected";
        } else {
            
            $createClaimsTableQuery = "CREATE TABLE IF NOT EXISTS InsuranceClaims (
                ClaimID INT AUTO_INCREMENT PRIMARY KEY,
                InsuranceID INT,
                BillID INT,
                ClaimAmount DECIMAL(10, 2),
                ClaimReason TEXT,
                ClaimDate DATE,
                ClaimStatus ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
                FOREIGN KEY(InsuranceID) REFERENCES Insurance(InsuranceID),
                FOREIGN KEY(BillID) REFERENCES Bills(BillID)
            )";
            
            if (mysqli_query($conn, $createClaimsTableQuery)) {
                $claimDate = date('Y-m-d');
                $insertClaimQuery = "INSERT INTO InsuranceClaims (InsuranceID, BillID, ClaimAmount, ClaimReason, ClaimDate) 
                                    VALUES ('{$insuranceInfo['InsuranceID']}', '$billID', '$claimAmount', '$claimReason', '$claimDate')";
                
                if (mysqli_query($conn, $insertClaimQuery)) {
                    $claimMessage = "Your insurance claim has been submitted successfully and is pending review";
                } else {
                    $claimError = "Failed to submit claim: " . mysqli_error($conn);
                }
            } else {
                $claimError = "System error: " . mysqli_error($conn);
            }
        }
    }
}


$claims = array();
$claimsQuery = "SHOW TABLES LIKE 'InsuranceClaims'";
$claimsTableExists = mysqli_query($conn, $claimsQuery);

if (mysqli_num_rows($claimsTableExists) > 0) {
    $getClaimsQuery = "SELECT ic.*, b.BillAmount, b.BillDate 
                      FROM InsuranceClaims ic 
                      JOIN Insurance i ON ic.InsuranceID = i.InsuranceID 
                      JOIN Bills b ON ic.BillID = b.BillID 
                      WHERE i.PatientID = '$patientID' 
                      ORDER BY ic.ClaimDate DESC";
    $claimsResult = mysqli_query($conn, $getClaimsQuery);
    
    if ($claimsResult && mysqli_num_rows($claimsResult) > 0) {
        while ($claim = mysqli_fetch_assoc($claimsResult)) {
            $claims[] = $claim;
        }
    }
}


function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, width=device-width">

    <link rel="stylesheet" href="../dashboard/style.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https:
    <link rel="stylesheet" href="style.css" />
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
                    <div>
                        <img src="../dashboard/icons/invoice.png" alt="">
                        <a href="../payments/index.php" style="text-decoration: none;">
                            <h6>Payments</h6>
                        </a>
                    </div>
                    <div class="active" style="height: 52.25px;">
                        <img src="images/insurance.png" alt="" style="height: 32.25px;">
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
                    <h1>Insurance Management</h1>
                </div>

                <?php if ($claimMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $claimMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($claimError): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $claimError; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Insurance Information Card -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Insurance Information</h5>
                                <?php if ($insuranceInfo): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateInsuranceModal">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if ($insuranceInfo): ?>
                                <div class="insurance-card">
                                    <div class="insurance-details">
                                        <div class="insurance-item">
                                            <label>Provider</label>
                                            <p><?php echo htmlspecialchars($insuranceInfo['ProviderName']); ?></p>
                                        </div>
                                        <div class="insurance-item">
                                            <label>Policy Number</label>
                                            <p><?php echo htmlspecialchars($insuranceInfo['PolicyNumber']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted small">
                                    <i class="fas fa-info-circle"></i> Your insurance information is used when processing your medical bills. You can submit claims for eligible medical expenses.
                                </p>
                                <?php else: ?>
                                <p class="no-insurance">
                                    <i class="fas fa-exclamation-triangle"></i> No insurance information found. Please add your insurance details to submit claims.
                                </p>
                                <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addInsuranceModal">
                                    <i class="fas fa-plus-circle"></i> Add Insurance Information
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Claim Section -->
                <?php if ($insuranceInfo): ?>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Submit Insurance Claim</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($claimableBills)): ?>
                                <p class="text-muted">You don't have any outstanding bills to claim at this time.</p>
                                <?php else: ?>
                                <form action="" method="post">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="bill_id" class="form-label">Select Bill</label>
                                            <select class="form-select" id="bill_id" name="bill_id" required>
                                                <option value="">-- Select a bill --</option>
                                                <?php foreach ($claimableBills as $bill): ?>
                                                <option value="<?php echo $bill['BillID']; ?>">
                                                    Bill #<?php echo $bill['BillID']; ?> - 
                                                    <?php echo date('M d, Y', strtotime($bill['BillDate'])); ?> - 
                                                    <?php echo formatCurrency($bill['BillAmount']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="claim_amount" class="form-label">Claim Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="claim_amount" name="claim_amount" step="0.01" min="0.01" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="claim_reason" class="form-label">Reason for Claim</label>
                                        <textarea class="form-control" id="claim_reason" name="claim_reason" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" name="submit_claim" class="btn btn-primary">
                                        <i class="fas fa-file-invoice-dollar"></i> Submit Claim
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Claims History Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Claims History</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($claims)): ?>
                                <div class="no-data">
                                    <p>No claims history available</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Claim ID</th>
                                                <th>Date</th>
                                                <th>Bill Ref</th>
                                                <th>Amount</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($claims as $claim): ?>
                                            <tr>
                                                <td>#<?php echo $claim['ClaimID']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($claim['ClaimDate'])); ?></td>
                                                <td>#<?php echo $claim['BillID']; ?></td>
                                                <td><?php echo formatCurrency($claim['ClaimAmount']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($claim['ClaimReason'], 0, 30)) . (strlen($claim['ClaimReason']) > 30 ? '...' : ''); ?></td>
                                                <td>
                                                    <?php if ($claim['ClaimStatus'] == 'Pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php elseif ($claim['ClaimStatus'] == 'Approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Insurance Modal -->
    <div class="modal fade" id="addInsuranceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Insurance Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process_insurance.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="providerName" class="form-label">Insurance Provider</label>
                            <input type="text" class="form-control" id="providerName" name="providerName" required>
                        </div>
                        <div class="mb-3">
                            <label for="policyNumber" class="form-label">Policy Number</label>
                            <input type="text" class="form-control" id="policyNumber" name="policyNumber" required>
                        </div>
                        <input type="hidden" name="action" value="add">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Insurance Information</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Insurance Modal -->
    <?php if ($insuranceInfo): ?>
    <div class="modal fade" id="updateInsuranceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Insurance Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process_insurance.php" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="updateProviderName" class="form-label">Insurance Provider</label>
                            <input type="text" class="form-control" id="updateProviderName" name="providerName" 
                                   value="<?php echo htmlspecialchars($insuranceInfo['ProviderName']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="updatePolicyNumber" class="form-label">Policy Number</label>
                            <input type="text" class="form-control" id="updatePolicyNumber" name="policyNumber" 
                                   value="<?php echo htmlspecialchars($insuranceInfo['PolicyNumber']); ?>" required>
                        </div>
                        <input type="hidden" name="insuranceID" value="<?php echo $insuranceInfo['InsuranceID']; ?>">
                        <input type="hidden" name="action" value="update">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Insurance Information</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
</body>

</html>