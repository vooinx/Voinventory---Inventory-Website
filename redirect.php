//Redirect Url: Google API login
<?php
require 'function.php';
require 'config.php';
require __DIR__ . "/vendor/autoload.php";

$client = new Google\Client;
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirecturi(GOOGLE_REDIRECT_URI);

if (!isset($_GET["code"])) {
    exit("Login failed: no code returned");
}

$token = $client->fetchAccessTokenWithAuthCode($_GET["code"]);

if (isset($token['error'])) {
    exit('Gagal mengambil access token: ' . htmlspecialchars($token['error']));
}

$client->setAccessToken($token['access_token']);

$oauth = new Google\Service\Oauth2($client);
$userinfo = $oauth->userinfo->get();

//menambahkan data user register ke db
$email = $userinfo->email;
$name = $userinfo->name;

// Cek apakah user sudah ada di database
$cek = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email'");
if (mysqli_num_rows($cek) == 0) {

    //generate adminID unique
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin WHERE adminID LIKE 'google%'");
    $row = mysqli_fetch_assoc($result);
    $totalGoogleUsers = $row['total'] + 1;

    // Buat hash password palsu
    $adminID = 'google' . str_pad($totalGoogleUsers, 3, '0', STR_PAD_LEFT);
    $fakePassword = md5('google_oauth'); 

    mysqli_query($conn, "INSERT INTO admin (nama, email, adminID, password) VALUES ('$name', '$email', '$adminID', '$fakePassword')");
}

$_SESSION['loggedin'] = true;
$_SESSION['email'] = $userinfo->email;
$_SESSION['name'] = $userinfo->name;

header('Location: index.php');
exit;
