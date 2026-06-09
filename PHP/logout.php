<?php
session_start();
session_unset();
session_destroy();
header("Location: ../prototypes/index.php");
exit();
?>