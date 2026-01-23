<?php
session_start();

$userAdmin = "admin@apex.com";
$password = "admin123";

if(isset($_POST['submit'])){
    // Merr te dhenat nga forma
    $email = $_POST['user'];
    $password = $_POST['pass'];
    // Kontrollon nese kredencialet jane te sakta
    if($email == $userAdmin && $password == $password){

        $_SESSION['user'] = $email;
        // Kontrollon nese eshte zgjedhur opsioni "Remember me"
        if(isset($_POST['remember'])){
            setcookie('user',$email, time() + 3600);
        }
        // Ridrejton ne dashboard
        header("Location: dashboard.php");
        exit();
    }
    // Nese kredencialet jane gabim
    else{
        echo "<h1 style='color:black;'>Username ose fjalekalimi eshte gabim!</h1>";
        echo " <a style='color:red;text-decoration:none; text-size:14px;' href='login.php'>Provoni perseri</a>";
    }
}

?>