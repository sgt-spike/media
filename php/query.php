<?php
   require_once "login.php";
   $conn = new mysqli($host, $user, $passw, $dbase);

   if ($conn->connect_error) die('Fatal Error');

   if (isset($_POST['where']))
   {
   	$results = $conn->query('SELECT title, media, genre FROM movies WHERE title LIKE "%' . $_POST['where'] . '%"');
   }
   else
   {
   	$results = $conn->query('SELECT title, media, genre FROM movies');
   }

   $conn->close();
?>
