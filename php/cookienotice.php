<?php
if (count($_COOKIE) > 0) {
   if(isset($_COOKIE['ChrissMedia'])) {
      echo $_COOKIE['ChrissMedia'];
   }
}
else {
   echo "No cookie found";
}

?>