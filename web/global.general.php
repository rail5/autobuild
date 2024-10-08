<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GPL 3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

/* General-purpose functions */
function random_string($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}

function file_not_empty($file) {
	return filesize($file) > 0;
}

function redirect_and_die($url, $params = false) {
    switch ($url) {
        case "back":
            $url = strtok($_SERVER['HTTP_REFERER'], "?");
            break;
        case "self":
            $url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]".parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
            break;
    }

    if ($params) {
    	$url .= "?".http_build_query($params);
    }
	header("location: $url");
	die();
}

function is_secure() {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| $_SERVER['SERVER_PORT'] == 443
            || $_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "127.0.0.1";
}

function remove_directory($directory) {
    if (!file_exists($directory)) {
        return true;
    }

    if (!is_dir($directory)) {
        return unlink($directory);
    }

    foreach (scandir($directory) as $file) {
        if ($file == '.'|| $file == '..') {
            continue;
        }

        if (!remove_directory($directory .'/'. $file)) {
            return false;
        }
    }

    return rmdir($directory);
}
