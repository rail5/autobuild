<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

if (!isset($_GET["log"])) {
	redirect_and_die("index.php");
}

$log_file = "";

if (!isset($_GET["tab"]) || $_GET["tab"] == "main") {
	$log_file = get_log_file($_GET["log"]);
} else {
	$log_file = get_package_build_log($_GET["log"], $_GET["tab"]);
}


?>
<!DOCTYPE html>
<html>
<head>
	<title>Log</title>
	<style>
		pre {
			height: auto;
			max-width: 800px;
			overflow: auto;
			background-color: #eeeeee;
			word-break: break-all !important;
			word-wrap: break-word !important;
			white-space: pre-wrap !important;
		}​
	</style>
</head>
<body>
<pre>
<?php

$build_log = file_get_contents($log_file);
$build_log = htmlentities($build_log);
echo $build_log;
?>
</pre>
<!-- For auto-focusing on the bottom -->
<div id="end"></div>
</body>
</html>
