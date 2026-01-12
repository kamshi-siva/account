<?php
session_start();

$_SESSION = [];

session_destroy();

header("Location: cashier_login.php");
exit();
