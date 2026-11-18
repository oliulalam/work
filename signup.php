<?php
session_start();
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: /player/signup.php" . $query);
exit();
