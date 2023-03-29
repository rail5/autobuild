<?php
/***
get-release-id.php:
	Scans a file provided  & outputs the ID
		The provided file should contain JSON response data from the GitHub API regarding a Release
	
	Usage:
		get-release-id.php -i ./file.json
***/

$opts = "i:";

$options = getopt($opts);

if (!isset($options["i"])) {
	echo "Error: Specify JSON file\n";
	die();
}

$data = file_get_contents($options["i"]);

$data = json_decode($data, true);

echo $data["id"];
