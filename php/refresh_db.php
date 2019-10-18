<?php

require_once "login.php";
$conn = new mysqli($host, $web_user, $passw, $dbase);

if ($conn->connect_error) die('Fatal Error');

   function refreshdb() {
      echo "Movie Library has been updated!";
   }

refreshdb();
/* $to_print = array("Track ID", "Name", "Kind"); */
?>