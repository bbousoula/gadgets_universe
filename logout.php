<?php
session_start();
// Flush active memory scope properties cleanly
$_SESSION = array();
// Destroy session tokens assigned on local system instances
session_destroy();
// Reroute back safely to standard authorization form layouts
header("Location: login.php");
exit();
?>