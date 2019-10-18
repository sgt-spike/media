<?php
require "connection.php";

   $query = 'SELECT title, media FROM movies';
   $results = mysqli_query($conn, $query);

   echo '<div class="container" id="main-all">
         <ul class="flex-container">';

   if (mysqli_num_rows($results) > 0) {
      while ($movie = mysqli_fetch_assoc($results) ){
         echo '<li class="flex-item flex-wrap">';
         echo '<div id="item-title">' . $movie['title'] . '</div>';
         echo '<div id="item-media">' . $movie['media'] . '</div>';
         echo '</li>';
      }
   }
   echo '</ul></div>';

?>