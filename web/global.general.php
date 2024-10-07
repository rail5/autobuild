<?php
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
            $url = $_SERVER['HTTP_REFERER'];
            break;
        case "self":
            $url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
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
			|| $_SERVER['SERVER_PORT'] == 443;
}
