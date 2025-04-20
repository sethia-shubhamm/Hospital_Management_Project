<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, width=device-width">

    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>" />
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
                    <a class="nav-link" href="../allDoctors/index.php">ALL DOCTORS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../bloodBank/index.php">BLOOD BANK</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../index.php#contact">CONTACT</a>
                </li>
            </ul>
            <div class="login">
                <a href="../login/index.php"><button type="button"
                        class="btn rounded-pill btn btn-primary">Login</button></a>
                <a href="../signUp/index.php"><button type="button" class="btn rounded-pill btn btn-primary">Sign
                        Up</button></a>
            </div>
        </div>
        <div class="mainContainer">
            <img src="images/doctorimg.png" alt="">
            <div class="loginForm">
                <div class="slider">
                    <button class="active"><a href="index.php">Patient</a></button>
                    <button><a href="../doctorLogin/index.php">Doctor</a></button>
                    <button><a href="../adminLogin/index.php">Admin</a></button>
                </div>
                <img src="images/Company.png" alt="">
                <h1>Patient Login</h1>

                <?php if (isset($_SESSION['login_error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $_SESSION['login_error'];
                        unset($_SESSION['login_error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['signup_success'])): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $_SESSION['signup_success'];
                        unset($_SESSION['signup_success']); ?>
                    </div>
                <?php endif; ?>

                <form id="loginForm" class="inputFeilds" action="login_process.php" method="POST">
                    <div class="right">
                        <label for="email">Enter Email :</label>
                        <input type="email" placeholder="xyz@gmail.com" name="email" id="email" required>
                        <label for="password">Enter Password :</label>
                        <input type="password" placeholder="Enter Password" name="password" id="password" required>
                        <input type="hidden" name="user_type" value="Patient">
                        <button type="submit" class="btn rounded-pill btn btn-primary">Login</button>
                        <div id="loginStatus" class="mt-2"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Floating Demo Login Button -->
    <button type="button" id="demoButton">
        <div class="floating-demo-btn">
            <div class="demo-icon">
                <span>Try Demo</span>
            </div>
        </div>
    </button>

    <script src="script.js"></script>

</body>

</html>