<?php
include "connection.php";
 
 if (isset($_POST['search'])){

	 $search = mysqli_real_escape_string($conn, $_POST['search']);
	 
	 $query = 'SELECT title, media FROM movies WHERE title LIKE "' .$search . '%"';
	 $results = mysqli_query($conn, $query);
	 $movies = mysqli_fetch_all($results, MYSQLI_ASSOC);
    echo json_encode($movies);
    } else {
    echo 'No Results...';
    }

mysqli_close($conn);
 ?>
