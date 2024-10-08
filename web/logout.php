<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GPL 3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";
$_SESSION['logged-in'] = false;
session_destroy();

header('location: login.php');
