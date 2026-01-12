<?php
session_start();
session_unset();
session_destroy();
header("Location: restaurant_login.php");
exit();
