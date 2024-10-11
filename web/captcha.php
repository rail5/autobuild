<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

session_start();

error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once "/usr/share/php/Gregwar/Captcha/autoload.php";

use Gregwar\Captcha\CaptchaBuilder;

$builder = new CaptchaBuilder;
$builder->build();

$_SESSION['captcha'] = $builder->getPhrase();

header('Content-Type: image/jpeg');
$builder->output();
