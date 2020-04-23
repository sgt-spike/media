<?php
//login.php
$host='10.20.30.10';
$dbase='moviesdb';
$web_user='webuser';
$passw='web@me';
$admin_user='moviedb_admin';
$admin_pw='KW3V4yd2VUmqgBZb';

$conn = mysqli_connect($host, $web_user, $passw, $dbase);

$admin_conn = mysqli_connect($host, $admin_user, $admin_pw, $dbase);

if (!$conn) {
   die('Connection failed: '.mysqli_connect_error());
}

if (!$admin_conn) {
   die('Connection failed: '.mysqli_connect_error());
}
?>