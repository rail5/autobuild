<?php
/***
get-pkg-info.php:
	Outputs the latest changelog entry or version number from Debian package sources
	
	Usage:
		get-pkg-info.php -i {package-src-directory} -c
		or
		get-pkg-info.php -i {package-src-directory} -v
	Example:
		get-pkg-info.php -i 2023-Mar-28-191410/srconly/liesel -c
			Would scan for the file:
				./2023-Mar-28-191410/srconly/liesel/debian/changelog
			And output the top changelog entry from that file
			
			Obviously running -v instead of -d would output the latest version number instead
***/

// We grep for the first occurence of this signature and stop there in our search
$signature = "rail5 <andrew@rail5.org";

$opts = "i:cv";

$options = getopt($opts);

if (!isset($options["i"])) {
	echo "Error: Specify package src directory\n";
	die();
}

$rootdirectory = $options["i"];

if (isset($options["c"])) {
	// Get changelog
	$changelog = file_get_contents("$rootdirectory/debian/changelog");

	if (!preg_match('/\*.*?'.$signature.'/s', $changelog, $matches)) {
		echo "Error";
		die();
	}

	$changelog = $matches[0];

	// Remove top asterisk
	$changelog = str_replace("* ", "", $changelog);
	// Remove signature
	$changelog = str_replace("\n -- $signature", "", $changelog);
	// Remove trailing spaces after newlines
	$changelog = str_replace("\n  ", "\n", $changelog);
	
	// Replace all newlines with spaces
	$changelog = str_replace("\n", " ", $changelog);

	echo $changelog;
} else if (isset($options["v"])) {
	// Get latest version
	$version = file_get_contents("$rootdirectory/debian/changelog");
	
	if (!preg_match('/\([0-9\.]*\)/', $version, $matches)) {
		echo "Error";
		die();
	}
	
	$version = $matches[0];
	
	// Remove surrounding parentheses
	$version = str_replace("(", "", $version);
	$version = str_replace(")", "", $version);
	
	echo $version;
} else {
	// Output error
	echo "Error: Specify -c for changelog or -v for version number";
	die();
}
