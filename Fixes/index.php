<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seattle Grace Hospital - Login</title>
    <link href="https:
    <link rel="stylesheet" href="https:
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            max-width: 450px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-top: 80px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #3f51b5;
            font-weight: 700;
        }

        .logo img {
            max-width: 80px;
            margin-bottom: 15px;
        }

        .btn-primary {
            background-color: #3f51b5;
            border-color: #3f51b5;
            padding: 10px 20px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #303f9f;
            border-color: #303f9f;
        }

        .form-control {
            padding: 12px;
            border: 1px solid #ddd;
        }

        .form-floating label {
            color: #666;
        }

        .user-type-selector {
            margin-bottom: 20px;
        }

        .user-type-selector .btn {
            width: 33.33%;
        }

        .btn-outline-primary {
            color: #3f51b5;
            border-color: #3f51b5;
        }

        .btn-check:checked+.btn-outline-primary {
            background-color: #3f51b5;
            border-color: #3f51b5;
        }

        .alert {
            border-radius: 5px;
            padding: 12px 15px;
        }

        .forgot-password {
            text-align: right;
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .forgot-password a {
            color: #3f51b5;
            text-decoration: none;
            font-size: 14px;
        }

        .bottom-text {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <img src="Frontend/assets/img/hospital-logo.png" alt="Hospital Logo"
                    onerror="this.src='https:
                <h1>Seattle Grace Hospital</h1>
                <p class="text-muted">Seattle Grace Hospital</p>
            </div>

            <?php
            session_start();

            
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }

            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>

            <div class="user-type-selector btn-group" role="group">
                <input type="radio" class="btn-check" name="userType" id="adminBtn" value="admin" autocomplete="off"
                    checked>
                <label class="btn btn-outline-primary" for="adminBtn">Admin</label>

                <input type="radio" class="btn-check" name="userType" id="doctorBtn" value="doctor" autocomplete="off">
                <label class="btn btn-outline-primary" for="doctorBtn">Doctor</label>

                <input type="radio" class="btn-check" name="userType" id="patientBtn" value="patient"
                    autocomplete="off">
                <label class="btn btn-outline-primary" for="patientBtn">Patient</label>
            </div>

            <form id="loginForm" action="Backend/authenticate.php" method="POST">
                <input type="hidden" id="userTypeInput" name="userType" value="admin">

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username"
                        required>
                    <label for="username">Username/Email</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                        required>
                    <label for="password">Password</label>
                </div>

                <div class="forgot-password">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Login <i class="fas fa-sign-in-alt ms-1"></i></button>
                </div>

                <div class="bottom-text">
                    <p id="registerText">Don't have an account? <a href="#" id="registerLink">Register now</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resetPasswordForm" action="Backend/reset_password.php" method="POST">
                        <div class="mb-3">
                            <label for="resetEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="resetEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <select class="form-select" name="resetUserType" required>
                                <option value="admin">Admin</option>
                                <option value="doctor">Doctor</option>
                                <option value="patient">Patient</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https:
    <script>
        
        document.querySelectorAll('input[name="userType"]').forEach(radio => {
            radio.addEventListener('change', function () {
                document.getElementById('userTypeInput').value = this.value;
                updateRegisterLink();
            });
        });

        
        function updateRegisterLink() {
            const userType = document.querySelector('input[name="userType"]:checked').value;
            const registerLink = document.getElementById('registerLink');
            const registerText = document.getElementById('registerText');

            if (userType === 'admin') {
                registerText.style.display = 'none';
            } else if (userType === 'doctor') {
                registerText.style.display = 'none';
            } else {
                registerText.style.display = 'block';
                registerLink.href = 'Frontend/register.php';
            }
        }

        
        document.addEventListener('DOMContentLoaded', updateRegisterLink);
    </script>
</body>

</html>