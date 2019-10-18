<?php
   require_once = "query.php"
   $rows = $results->num_rows;

   for ($j = 0; $j < $rows; ++$j)
   {
   	$row = $results->fetch_array(MYSQLI_ASSOC);
   	echo '<li><p id="title">' . htmlspecialchars($row['title']) . '</p>
            <p id="media">' . htmlspecialchars($row['media']) . '</p></li>';
   }

   $results->close();
 ?>
