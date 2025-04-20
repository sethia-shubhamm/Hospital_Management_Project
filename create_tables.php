<?php

require_once 'db_connect.php';


function logError($message)
{
    $logFile = "error_log.txt";
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}


$sql = "CREATE DATABASE IF NOT EXISTS hospital_management_system";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully or already exists<br>";
} else {
    logError("Error creating database: " . mysqli_error($conn));
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}


mysqli_select_db($conn, "hospital_management_system");


$sql = "CREATE TABLE IF NOT EXISTS Admin (
    AdminID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Phone VARCHAR(15),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Admin table created successfully<br>";
} else {
    logError("Error creating Admin table: " . mysqli_error($conn));
    echo "Error creating Admin table: " . mysqli_error($conn) . "<br>";
}


$sql = "CREATE TABLE IF NOT EXISTS Doctors (
    DoctorID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Specialization VARCHAR(100) NOT NULL,
    Gender VARCHAR(10) NOT NULL,
    Phone VARCHAR(15) NOT NULL,
    Address TEXT,
    Education TEXT,
    Experience TEXT,
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Doctors table created successfully<br>";
} else {
    logError("Error creating Doctors table: " . mysqli_error($conn));
    echo "Error creating Doctors table: " . mysqli_error($conn) . "<br>";
}


$sql = "CREATE TABLE IF NOT EXISTS Patients (
    PatientID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Email VARCHAR(100) UNIQUE,
    Password VARCHAR(255),
    DateOfBirth DATE NOT NULL,
    Gender VARCHAR(10) NOT NULL,
    Phone VARCHAR(15) NOT NULL,
    Address TEXT,
    BloodType VARCHAR(5),
    MedicalHistory TEXT,
    EmergencyContact VARCHAR(100),
    EmergencyPhone VARCHAR(15),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    TotalBilling DECIMAL(10,2) DEFAULT 0.00
)";

if (mysqli_query($conn, $sql)) {
    echo "Patients table created successfully<br>";
} else {
    logError("Error creating Patients table: " . mysqli_error($conn));
    echo "Error creating Patients table: " . mysqli_error($conn) . "<br>";
}


$sql = "CREATE TABLE IF NOT EXISTS Appointments (
    AppointmentID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID INT NOT NULL,
    DoctorID INT NOT NULL,
    AppointmentDate DATE NOT NULL,
    AppointmentTime TIME NOT NULL,
    Reason TEXT,
    Status VARCHAR(20) DEFAULT 'Scheduled',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PatientID) REFERENCES Patients(PatientID) ON DELETE CASCADE,
    FOREIGN KEY (DoctorID) REFERENCES Doctors(DoctorID) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Appointments table created successfully<br>";
} else {
    logError("Error creating Appointments table: " . mysqli_error($conn));
    echo "Error creating Appointments table: " . mysqli_error($conn) . "<br>";
}


$sql = "CREATE TABLE IF NOT EXISTS Bills (
    BillID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID INT NOT NULL,
    AppointmentID INT,
    BillAmount DECIMAL(10,2) NOT NULL,
    Description TEXT,
    BillType VARCHAR(50) NOT NULL,
    BillDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PaymentStatus VARCHAR(20) DEFAULT 'Unpaid',
    PaymentMethod VARCHAR(50),
    PaymentDate DATETIME,
    PartialAmount DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (PatientID) REFERENCES Patients(PatientID) ON DELETE CASCADE,
    FOREIGN KEY (AppointmentID) REFERENCES Appointments(AppointmentID) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "Bills table created successfully<br>";
} else {
    logError("Error creating Bills table: " . mysqli_error($conn));
    echo "Error creating Bills table: " . mysqli_error($conn) . "<br>";
}


$sql = "CREATE TABLE IF NOT EXISTS BloodInventory (
    InventoryID INT AUTO_INCREMENT PRIMARY KEY,
    BloodType VARCHAR(5) NOT NULL,
    Units INT DEFAULT 0,
    LastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "BloodInventory table created successfully<br>";
} else {
    logError("Error creating BloodInventory table: " . mysqli_error($conn));
    echo "Error creating BloodInventory table: " . mysqli_error($conn) . "<br>";
}


$sql = "CREATE TABLE IF NOT EXISTS BloodDonors (
    DonorID INT AUTO_INCREMENT PRIMARY KEY,
    DonorName VARCHAR(100) NOT NULL,
    BloodType VARCHAR(5) NOT NULL,
    Phone VARCHAR(15) NOT NULL,
    Email VARCHAR(100),
    DonationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Units INT DEFAULT 1,
    Status VARCHAR(20) DEFAULT 'Completed'
)";

if (mysqli_query($conn, $sql)) {
    echo "BloodDonors table created successfully<br>";
} else {
    logError("Error creating BloodDonors table: " . mysqli_error($conn));
    echo "Error creating BloodDonors table: " . mysqli_error($conn) . "<br>";
}


$sql = "CREATE TABLE IF NOT EXISTS BloodRequests (
    RequestID INT AUTO_INCREMENT PRIMARY KEY,
    PatientName VARCHAR(100) NOT NULL,
    PatientID INT,
    BloodType VARCHAR(5) NOT NULL,
    Units INT DEFAULT 1,
    RequestDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status VARCHAR(20) DEFAULT 'Pending',
    Phone VARCHAR(15) NOT NULL,
    Email VARCHAR(100),
    FOREIGN KEY (PatientID) REFERENCES Patients(PatientID) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "BloodRequests table created successfully<br>";
} else {
    logError("Error creating BloodRequests table: " . mysqli_error($conn));
    echo "Error creating BloodRequests table: " . mysqli_error($conn) . "<br>";
}


$bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
foreach ($bloodTypes as $type) {
    $sql = "INSERT IGNORE INTO BloodInventory (BloodType, Units) VALUES ('$type', 0)";
    if (!mysqli_query($conn, $sql)) {
        logError("Error initializing blood inventory for $type: " . mysqli_error($conn));
        echo "Error initializing blood inventory for $type: " . mysqli_error($conn) . "<br>";
    }
}


$adminCheck = "SELECT * FROM Admin LIMIT 1";
$result = mysqli_query($conn, $adminCheck);

if (mysqli_num_rows($result) == 0) {
    
    $defaultUsername = "admin";
    $defaultPassword = password_hash("admin123", PASSWORD_DEFAULT);
    $defaultEmail = "admin@seattlegrace.hospital";

    $sql = "INSERT INTO Admin (Username, Password, Email, FirstName, LastName, Phone) 
            VALUES ('$defaultUsername', '$defaultPassword', '$defaultEmail', 'Admin', 'User', '555-123-4567')";

    if (mysqli_query($conn, $sql)) {
        echo "Default admin account created successfully<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        logError("Error creating default admin account: " . mysqli_error($conn));
        echo "Error creating default admin account: " . mysqli_error($conn) . "<br>";
    }
}

echo "<p>Database setup completed successfully!</p>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";

mysqli_close($conn);
?>