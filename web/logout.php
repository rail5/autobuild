<?php
require_once "global.php";
$_SESSION['logged-in'] = false;
session_destroy();

header('location: login.php');
