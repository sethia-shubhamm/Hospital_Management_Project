<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
</head>

<body>
    <div class="desktop">
        <div class="navbar">
            <div class="logo">
                <img src="images/logo.png" alt=""> 
                <h6>Seattle Grace Hospital</h6>
            </div>
            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="../index.php">HOME</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../allDoctors/index.html">ALL DOCTORS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../bloodBank/index.html">BLOOD BANK</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../index.php#contact">CONTACT</a>
                </li>
            </ul>
            <div class="login">
                <a href="../login/index.php"><button type="button"
                        class="btn rounded-pill btn btn-primary">Login</button></a>
                <a href="index.php"><button type="button" class="btn rounded-pill btn btn-primary">Sign Up</button></a>
            </div>
        </div>
        <div class="mainContainer">
            <img src="images/doctorimg.png" alt="">
            <div class="loginForm">
                <h1>Register as Patient</h1>

                <?php if (isset($_SESSION['signup_error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $_SESSION['signup_error'];
                        unset($_SESSION['signup_error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Using a direct HTML form to avoid JavaScript issues -->
                <form action="signup_process.php" method="POST" class="inputFeilds">
                    <div class="left">
                        <input type="text" placeholder="Name" name="name" id="name" required>
                        <input type="email" placeholder="Email" name="email" id="email" required>
                        <input type="password" placeholder="Password" name="password" id="password" required>
                        <input type="number" placeholder="Age" name="age" id="age">
                        <div class="gender">
                            <input type="radio" name="gender" id="male" value="Male">Male
                            <input type="radio" name="gender" id="female" value="Female">Female
                        </div>
                        <p>Already have an account? <a href="../login/index.php">Login</a></p>
                    </div>
                    <div class="right">
                        <input type="text" placeholder="Phone Number" name="contact_info" id="contact_info">
                        <input type="text" placeholder="Address" name="address" id="address">
                        <input type="password" placeholder="Confirm Password" id="confirmPassword">
                        <select name="blood_type" class="form-select" id="blood_type">
                            <option value="" disabled selected>Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                        <input type="hidden" name="user_type" value="Patient">
                        <button type="submit" class="btn rounded-pill btn btn-primary">Sign Up</button>
                        <div id="signUpStatus" class="mt-2"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Simple inline script for validation without preventing form submission -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            const signUpStatus = document.getElementById('signUpStatus');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');

            if (form) {
                form.addEventListener('submit', function (e) {
                    
                    if (password.value !== confirmPassword.value) {
                        e.preventDefault();
                        signUpStatus.innerHTML = '<div class="alert alert-danger">Passwords do not match</div>';
                        return false;
                    }
                    return true;
                });
            }
        });
    </script>
</body>

</html>