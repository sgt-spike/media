<?php
$cookie_name = 'ChrissMedia';
$cookie_value = 'Cookie is Set';
$cookie_time = time() + (86400 * 30);
$cookie_domain = 'media.spikedevelopments.com';
setcookie($cookie_name, $cookie_value, $cookie_time, $cookie_domain);
?>