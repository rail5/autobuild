<?php
/***
get-changelog.php:
	Outputs the latest changelog entry for BookThief or Liesel
	
	Usage:
		get-changelog.php -d {root-build-directory} -b
		or
		get-changelog.php -d {root-build-directory} -l
	Example:
		get-changelog.php -d 2023-Mar-28-191410 -l
			Would scan for the file:
				./2023-Mar-28-191410/deb/liesel/debian/changelog
			And output the top changelog entry from that file
			Obviously using -b rather than -l would scan for BookThief's changelog rather than Liesel's
***/

$which_changelog = "none";

$opts = "d:bl";

$options = getopt($opts);

if (!isset($options["d"])) {
	echo "Error: Specify build directory\n";
	die();
}

if (isset($options["b"])) {
	$which_changelog = "bookthief";
} else if (isset($options["l"])) {
	$which_changelog = "liesel";
} else {
	echo "Error: Specify -b for BookThief's changelog, or -l for Liesel's changelog\n";
	die();
}

$rootdirectory = $options["d"];

$changelog = file_get_contents("$rootdirectory/deb/$which_changelog/debian/changelog");

if (!preg_match('/\*.*?rail5 <andrew@rail5.org>/s', $changelog, $matches)) {
	echo "Error";
	die();
}

$changelog = $matches[0];

// Remove top asterisk
$changelog = str_replace("* ", "", $changelog);
// Remove signature
$changelog = str_replace("\n -- rail5 <andrew@rail5.org>", "", $changelog);
// Remove trailing spaces after newlines
$changelog = str_replace("\n  ", "\n", $changelog);

echo $changelog;
