<?php
session_start();
// Fshin te gjitha te dhenat e session
session_destroy();
// Fshin cookie nese ekziston
if(isset($_COOKIE['user'])){
    setcookie('user', "", time() - 3600);
}
// Ridrejton ne faqen e login
header("Location:../HTML/login.php");
exit();

?>