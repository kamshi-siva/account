<?php
session_start();
session_unset();
session_destroy();
header("Location: cashier_login.php");
exit();
