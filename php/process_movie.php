<?php // process_movie.php
   /* This scripts processes a movies file and returns an array of tag information */
   include 'php/getID3/getid3/getid3.php';
   $id = new getID3;
   function get_metadata($file) {
      
      $fileInfo = $id->analyze($file);
      $id->CopyTagsToComments($fileInfo);
      return $fileInfo;
   }
?>