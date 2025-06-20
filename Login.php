<?php
// session_start();
require 'config.php';
require 'function.php';
require __DIR__ . "/vendor/autoload.php";

//Google API login
$client = new Google\Client;

$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirecturi(GOOGLE_REDIRECT_URI);

$client->addScope("email");
$client->addScope("profile");

$url = $client->createAuthUrl();

//Create
if (isset($_POST['signup'])) {
    $nama = $_POST['name']; 
    $email = $_POST['email'];
    $adminID = $_POST['adminID'];
    $password = md5($_POST['password']); // Hash MD5

    $query = "INSERT INTO admin (nama, email, adminID, password) VALUES ('$nama', '$email', '$adminID',  '$password')";
    
    if (mysqli_query($conn, $query)) {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Sign Up Berhasil!',
                text: 'Silakan login untuk melanjutkan.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'login.php';
            });
        });
    </script>";
    exit(); 
}

}

//Read
$error = "";

if(isset($_POST['login'])){
    $adminID = $_POST['adminID'];
    $password = md5($_POST['password']);

    $cekdatabase = mysqli_query($conn, "SELECT * FROM admin where adminID='$adminID' and password='$password'");

    $hitung = mysqli_num_rows($cekdatabase);

    if($hitung > 0){
        $user_data = mysqli_fetch_assoc($cekdatabase);
        $_SESSION['loggedin'] = true;
        $_SESSION['adminID'] = $adminID; // Store adminID for regular users
        $_SESSION['user_nama'] = $user_data['nama']; // Store user name
        header('location:index.php');
        exit();
    } else {
        $error = "Incorrect email or password.";
    }
};

if(isset($_SESSION['loggedin'])){ // Check for 'loggedin' session variable
    header('location:index.php');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOINVENTORY</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="assets/img/LogoBesar.png">
    <link rel="stylesheet" href="assets/css/portal-login.css">

</head>

<body>
    <div class="container" id="container">
        <div class="form-container sign-up">
            <form method="POST" action="">
                <h1>Create Account</h1>
                <div class="social-icons">
                    <a href="<?= $url ?>" class="icon"><i class="fa-brands fa-google"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                    <!-- <a href="#" class="icon"><i class="fa-brands fa-github"></i></a> -->
                    <!-- <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a> -->
                </div>
                <span>or create your ID for registration</span>
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="adminID" placeholder="ID" required>
                <div class="password-wrapper">
                    <input type="password" name="password" id="signupPassword" placeholder="Password" required>
                    <i class="fa-solid fa-eye-slash" id="toggleSignupPassword"></i>
                </div>
                <button type="submit" name="signup">Sign Up</button>
            </form>
        </div>

        <div class="form-container sign-in">
            <form method="POST" action="">
                <h1>Sign In</h1>
                <div class="social-icons">
                    <a href="<?= $url ?>" class="icon"><i class="fa-brands fa-google"></i></a>
                    <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
                    <!-- <a href="#" class="icon"><i class="fa-brands fa-github"></i></a> -->
                    <!-- <a href="#" class="icon"><i class="fa-brands fa-linkedin-in"></i></a> -->
                </div>
                <span>or use your ID password</span>
                <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
                <input type="text" name="adminID" placeholder="ID" required>
                <div class="password-wrapper">
                    <input type="password" name="password" id="signinPassword" placeholder="Password" required>
                    <i class="fa-solid fa-eye-slash" id="toggleSigninPassword"></i>
                </div>
                <div class="options-row">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember Me
                    </label>
                    <a href="#" class="forgot-btn">Forgot Your Password?</a>
                </div>
                <button type="submit" name="login">Sign In</button>
            </form>
        </div>

        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <!-- <h1>Welcome Back!</h1> -->
                    <img src="assets/img/Logo2.png" alt="Voinventory Logo" class="logo-img">
                    <p>Enter your personal details to <br> manage deposits and inventory</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <img src="assets/img/Logo2.png" alt="Voinventory Logo" class="logo-img">
                    <!-- <h1>Hello, Friend!</h1> -->
                    <p>Staff access only — login to manage <br> deposits and inventory</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script src="src/js/script.js"></script>
</body>
</html>
