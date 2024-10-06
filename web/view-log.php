<?php
require_once "global.php";

$log_file = get_log_file($_GET["log"]);
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