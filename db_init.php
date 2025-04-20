<?php
require_once 'db_connect.php';


function tablesExist($conn)
{
    $query = "SHOW TABLES LIKE 'Patients'";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}


function testUsersExist($conn)
{
    $query = "SELECT * FROM LoginCredentials WHERE Email IN ('admin@hospital.com', 'doctor@hospital.com', 'patient@hospital.com')";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}


if (!tablesExist($conn)) {
    
    $tableQueries = [
        
        "CREATE TABLE IF NOT EXISTS Patients(
            PatientID INT AUTO_INCREMENT PRIMARY KEY,
            PatientName VARCHAR(100) NOT NULL,
            PatientAge INT,
            PatientGender ENUM('Male', 'Female', 'Other'),
            BloodType VARCHAR(5),
            ContactInfo VARCHAR(255)
        )",

        
        "CREATE TABLE IF NOT EXISTS Insurance(
            InsuranceID INT AUTO_INCREMENT PRIMARY KEY,
            PatientID INT,
            ProviderName VARCHAR(100),
            PolicyNumber VARCHAR(50),
            FOREIGN KEY(PatientID) REFERENCES Patients(PatientID)
        )",

        
        "CREATE TABLE IF NOT EXISTS Doctors(
            DoctorID INT AUTO_INCREMENT PRIMARY KEY,
            DoctorName VARCHAR(100) NOT NULL,
            Specialty VARCHAR(100)
        )",

        
        "CREATE TABLE IF NOT EXISTS MedicalRecords(
            RecordID INT AUTO_INCREMENT PRIMARY KEY,
            PatientID INT,
            MedicalCondition VARCHAR(255),
            TreatmentInfo TEXT,
            DoctorID INT,
            RecordDate DATE,
            FOREIGN KEY(PatientID) REFERENCES Patients(PatientID),
            FOREIGN KEY(DoctorID) REFERENCES Doctors(DoctorID)
        )",

        
        "CREATE TABLE IF NOT EXISTS Appointments(
            AppointmentID INT AUTO_INCREMENT PRIMARY KEY,
            PatientID INT,
            DoctorID INT,
            AppointmentDate DATE,
            AppointmentTime TIME,
            FOREIGN KEY(PatientID) REFERENCES Patients(PatientID),
            FOREIGN KEY(DoctorID) REFERENCES Doctors(DoctorID)
        )",

        
        "CREATE TABLE IF NOT EXISTS BloodDonors(
            DonorID INT AUTO_INCREMENT PRIMARY KEY,
            DonorName VARCHAR(100) NOT NULL,
            BloodType VARCHAR(5),
            DonationDate DATE
        )",

        
        "CREATE TABLE IF NOT EXISTS BloodInventory(
            InventoryID INT AUTO_INCREMENT PRIMARY KEY,
            BloodType VARCHAR(5) NOT NULL,
            Quantity INT NOT NULL,
            ExpiryDate DATE
        )",

        
        "CREATE TABLE IF NOT EXISTS BloodRequests(
            RequestID INT AUTO_INCREMENT PRIMARY KEY,
            PatientID INT,
            BloodType VARCHAR(5) NOT NULL,
            Quantity INT NOT NULL,
            RequestStatus ENUM('Pending', 'Approved', 'Rejected', 'Fulfilled') DEFAULT 'Pending',
            FOREIGN KEY(PatientID) REFERENCES Patients(PatientID)
        )",

        
        "CREATE TABLE IF NOT EXISTS Bills(
            BillID INT AUTO_INCREMENT PRIMARY KEY,
            PatientID INT,
            BillDate DATE,
            BillAmount DECIMAL(10, 2),
            FOREIGN KEY(PatientID) REFERENCES Patients(PatientID)
        )",

        
        "CREATE TABLE IF NOT EXISTS Payments(
            PaymentID INT AUTO_INCREMENT PRIMARY KEY,
            BillID INT,
            PaymentDate DATE,
            AmountPaid DECIMAL(10, 2),
            FOREIGN KEY(BillID) REFERENCES Bills(BillID)
        )",

        
        "CREATE TABLE IF NOT EXISTS Admins(
            AdminID INT AUTO_INCREMENT PRIMARY KEY,
            AdminName VARCHAR(100) NOT NULL,
            AdminRole VARCHAR(50)
        )",

        
        "CREATE TABLE IF NOT EXISTS SystemLogs(
            LogID INT AUTO_INCREMENT PRIMARY KEY,
            AdminID INT,
            ActionDesc TEXT,
            LogTimestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(AdminID) REFERENCES Admins(AdminID)
        )",

        
        "CREATE TABLE IF NOT EXISTS LoginCredentials(
            LoginID INT AUTO_INCREMENT PRIMARY KEY,
            Email VARCHAR(100) UNIQUE NOT NULL,
            UserPassword VARCHAR(255) NOT NULL,
            UserType ENUM('Admin', 'Doctor', 'Patient') NOT NULL
        )"
    ];

    
    foreach ($tableQueries as $query) {
        mysqli_query($conn, $query);
    }
}


if (!testUsersExist($conn)) {
    
    $admin_query = "INSERT INTO Admins (AdminName, AdminRole) VALUES ('Admin User', 'System Admin')";
    if (mysqli_query($conn, $admin_query)) {
        $admin_id = mysqli_insert_id($conn);
        $admin_login_query = "INSERT INTO LoginCredentials (Email, UserPassword, UserType) 
                            VALUES ('admin@hospital.com', 'admin123', 'Admin')";
        mysqli_query($conn, $admin_login_query);
    }

    
    $doctor_query = "INSERT INTO Doctors (DoctorName, Specialty) VALUES ('Dr. John Smith', 'Cardiologist')";
    if (mysqli_query($conn, $doctor_query)) {
        $doctor_id = mysqli_insert_id($conn);
        $doctor_login_query = "INSERT INTO LoginCredentials (Email, UserPassword, UserType) 
                             VALUES ('doctor@hospital.com', 'doctor123', 'Doctor')";
        mysqli_query($conn, $doctor_login_query);
    }

    
    $patient_query = "INSERT INTO Patients (PatientName, PatientAge, PatientGender, BloodType, ContactInfo) 
                     VALUES ('Patient User', 35, 'Male', 'O+', '555-123-4567')";
    if (mysqli_query($conn, $patient_query)) {
        $patient_id = mysqli_insert_id($conn);
        $patient_login_query = "INSERT INTO LoginCredentials (Email, UserPassword, UserType) 
                              VALUES ('patient@hospital.com', 'patient123', 'Patient')";
        mysqli_query($conn, $patient_login_query);
    }

    
    $today = date('Y-m-d');
    $time = '14:30:00'; 
    $appt_query = "INSERT INTO Appointments (PatientID, DoctorID, AppointmentDate, AppointmentTime)
                   VALUES (1, 1, '$today', '$time')";
    mysqli_query($conn, $appt_query);
}


mysqli_close($conn);
?>