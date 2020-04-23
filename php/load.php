<?php
require "connection.php";

   echo '<div class="container" id="main-all">
         <ul class="flex-container">';

   if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $query = "SELECT movie_id, title, media FROM movies WHERE title LIKE `{$_GET['search']}`";
      
      if ($movies = mysqli_query($conn, $query)) {
         while ($movie = mysqli_fetch_array($movies)) {
            echo '<li class="flex-item flex-wrap">';
            echo "<a href=\"index.php?select={$movie['id']}\"></a>"
            echo '<div id="item-title">' . $movie['title'] . '</div>';
            echo '<div id="item-media">' . $movie['media'] . '</div>';
            echo '</a>';
            echo '</li>';
         }
      }
   } else {
      $query = 'SELECT movie_id, title, media FROM movies';
      if ($movies = mysqli_query($conn, $query)) {
         while ($movie = mysqli_fetch_assoc($movies) ){
            echo '<li class="flex-item flex-wrap">';
            echo "<a href=\"index.php?select={$movie['id']}\"></a>"
            echo '<div id="item-title">' . $movie['title'] . '</div>';
            echo '<div id="item-media">' . $movie['media'] . '</div>';
            echo '</a>';
            echo '</li>';
         }
      }
   }
         
   echo '</ul></div>';

?>
