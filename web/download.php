<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

$autobuild_directory = "/var/autobuild";
$autobuild_builds_directory = "$autobuild_directory/builds";

$error_params = array( "error" => "invalid-file");

if (!isset($_GET["jobid"]) || !isset($_GET["pkg"]) || !isset($_GET["file"])) {
	redirect_and_die("index.php");
}

$jobid = $_GET["jobid"];
$pkg = $_GET["pkg"];
$file = $_GET["file"];

/* Validate the info */

// Do we have files for the given Job ID?
$job_path = "$autobuild_builds_directory/$jobid";
$valid_jobid = file_exists($job_path)
		&& dirname(realpath($job_path)) == $autobuild_builds_directory;

if (!$valid_jobid) {
	redirect_and_die("index.php", $error_params);
}


// Was this package among the ones built for this Job ID?
$pkg_path = "$job_path/$pkg";
$valid_pkg = file_exists($pkg_path)
		&& dirname(realpath($pkg_path)) == $job_path;

if (!$valid_pkg) {
	redirect_and_die("index.php", $error_params);
}


// Was this file built?
$file_path = "$pkg_path/$file";
$valid_file = file_exists($file_path)
		&& dirname(realpath($file_path)) == $pkg_path;

if (!$valid_file) {
	redirect_and_die("index.php", $error_params);
}


// Finally, give them the file:
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"$file\"");
readfile($file_path);
