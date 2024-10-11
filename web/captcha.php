<?php

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
