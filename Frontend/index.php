<?php

require_once '../db_init.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="mainDashboard.css">
    <title>Seattle Grace Hospital</title>
</head>

<body>
    <div class="container">
        <nav class="navbar">
            <div class="logo">
                <img src="signUp/images/logo.png" alt="Hospital Logo"> 
                <h1>Seattle Grace Hospital</h1>
            </div>
            <div class="nav-links">
                <a href="allDoctors/index.php">All Doctors</a>
                <a href="bloodBank/index.php">Blood Bank</a>
                <a href="#services">Services</a>
                <a href="#contact">Contact</a>
            </div>
        </nav>

        <div class="hero-section">
            <h1>Welcome to Our Healthcare Platform</h1>
            <p>Providing quality healthcare services</p>
        </div>

        <div class="stats-section">
            <div class="stat-card">
                <h3>700+</h3>
                <p>Specialist Doctors</p>
            </div>
            <div class="stat-card">
                <h3>10000+</h3>
                <p>Happy Patients</p>
            </div>
            <div class="stat-card">
                <h3>24/7</h3>
                <p>Emergency Service</p>
            </div>
            <div class="stat-card">
                <h3>15+</h3>
                <p>Years Experience</p>
            </div>
        </div>

        <div class="login-options">
            <div class="card patient">
                <img src="mainImages/patient.png" alt="Patient">
                <h2>Patient Portal</h2>
                <p>Access your medical records, appointments, and more</p>
                <div class="buttons">
                    <a href="login/index.php" class="btn login">Login</a>
                    <a href="signUp/index.php" class="btn signup">Sign Up</a>
                </div>
            </div>

            <div class="card doctor">
                <img src="mainImages/doctor.png" alt="Doctor">
                <h2>Doctor Portal</h2>
                <p>Manage your patients view your appointments</p>
                <a href="doctorLogin/index.php" class="btn login">Doctor Login</a>
            </div>

            <div class="card admin">
                <img src="mainImages/admin.png" alt="Admin">
                <h2>Admin Portal</h2>
                <p>Hospital management and system administration</p>
                <a href="adminLogin/index.php" class="btn login">Admin Login</a>
            </div>
        </div>

        <div class="services-section" id="services">
            <h2>Our Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <img src="mainImages/ambulance.png" alt="Emergency">
                    <h3>24/7 Emergency</h3>
                    <p>Round-the-clock emergency medical services</p>
                </div>
                <div class="service-card">
                    <img src="mainImages/medical-appointment.png" alt="Appointment">
                    <h3>Appointments</h3>
                    <p>Schedule appointments with your preferred doctors through our system.</p>
                </div>
                <div class="service-card">
                    <img src="mainImages/vaccine-record.png" alt="Records">
                    <h3>Medical Records</h3>
                    <p>Access your medical history and records securely from anywhere.</p>
                </div>
            </div>
        </div>

        <footer id="contact">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p>📞 Emergency: 911</p>
                    <p>📱 Helpline: +1 234 567 890</p>
                    <p>📧 Email: info@hospital.com</p>
                </div>
                <div class="footer-section">
                    <h3>Location</h3>
                    <p>123 Healthcare Street</p>
                    <p>Medical City, MC 12345</p>
                    <p>India</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="allDoctors/index.php">All Doctors</a>
                    <a href="bloodBank/index.php">Blood Bank</a>
                    <a href="#services">Services</a>
                    <a href="login/index.php">Patient Login</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Seattle Grace Hospital. All rights reserved.</p>

                <!-- Hidden credentials for testing -->
                <div style="display: none;">
                    <p>Test Accounts:</p>
                    <ul>
                        <li>Admin: admin@hospital.com / admin123</li>
                        <li>Doctor: doctor@hospital.com / doctor123</li>
                        <li>Patient: patient@hospital.com / patient123</li>
                    </ul>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>