<?php
   //require 'php/cookie.php';
   include 'header.php';
   include "php/connection.php";
   ini_set('memory_limit','256M');
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   
   $tbl = 'movies';
   $countMovies = "SELECT COUNT(*) movie_count FROM ".$tbl;
   $row = mysqli_query($conn, $countMovies);
   $movieCount = mysqli_fetch_array($row);
   echo '<div>Movie Count: '.$movieCount['movie_count'].'</div>';
   echo '<div class="main__content" id="main-all">';
   echo '<ul class="movies movie--grid">';
         
   if (!isset($_GET['search'])) {
      
      $query = 'SELECT movie_id, title, coverpath FROM movies ORDER BY title, creation_date';
      if ($movies = mysqli_query($conn, $query)) {
         while ($movie = mysqli_fetch_assoc($movies) ){
            echo '<li class="list__item">';
            echo "<a class=\"aLink movie--link\" href=\"index.php?select={$movie['movie_id']}\">";
            echo '<div class="movie__item">';
            echo "<div class=\"movie__cover\"><img src=\"{$movie['coverpath']}\"></div>";
            echo '<div id="movie__title">' . $movie['title'] . '</div>';
            echo '</div>';
            echo '</a>';
            echo '</li>';
         }
      }
   } elseif (isset($_GET['search'])) {
      $query = "SELECT movie_id, title, media, cover FROM movies_all WHERE title REGEXP '({$_GET['search']})'";
      if ($movies = mysqli_query($conn, $query)) {
         while ($movie = mysqli_fetch_array($movies)) {
            echo '<li class="list__item">';
            echo "<a class=\"aLink\" href=\"index.php?select={$movie['movie_id']}\">";
            echo '<div class="movie__item">';
            echo '<div class="movie__cover"><img src="data:image/jpeg;base64,' . base64_encode($movie['cover']) . '"></div>';
            echo '<div id="movie__title">' . $movie['title'] . '</div>';
            echo '<div id="movie__media">' . $movie['media'] . '</div>';
            echo '</div>';
            echo '</a>';
            echo '</li>';
         }
      }
   }
         
   echo '</ul></div>';
   include 'footer.php';
?>
