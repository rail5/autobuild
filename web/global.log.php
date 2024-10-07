<?php
require_once "global.php";

/* Logging & log file functions */

$log_directory = "/var/autobuild/web/log";
$autobuild_directory = "/var/autobuild";
$autobuild_repos_directory = "$autobuild_directory/repo";
$autobuild_builds_directory = "$autobuild_directory/builds";

function get_build_logs() {
	global $log_directory;
	return array_filter(glob("$log_directory/*.log"), 'file_not_empty');
}
function get_log_file($log_number) {
	global $log_directory;
	$log_file = "$log_directory/$log_number.log";
	
	$valid_log = file_exists($log_file)
		&& dirname(realpath($log_file)) == $log_directory;
	
	if (!$valid_log) {
		$_GET["error"] = "invalid-log";
		redirect_and_die("back", $_GET);
		return "";
	}

	return $log_file;
}

function create_log_file() {
	global $log_directory;
	$log_number = random_string();
	$log_file = "$log_directory/$log_number.log";

	while (file_exists($log_file)) {
		$log_number = random_string();
		$log_file = "$log_directory/$log_number.log";
	}

	file_put_contents($log_file, "");

	// Make sure the autobuild user can write to the log file
	chmod($log_file, 0770);

	return $log_file;
}

function delete_log($log_number) {
	global $log_directory;
	$log_file = "$log_directory/$log_number.log";
	
	$valid_log = file_exists($log_file)
		&& dirname(realpath($log_file)) == $log_directory;
	
	if (!$valid_log) {
		$_GET["error"] = "invalid-log";
		redirect_and_die("back", $_GET);
		return "";
	}

	unlink($log_file);
}

function delete_all_logs() {
	global $log_directory;
	$logs = array_filter(glob("$log_directory/*.log"));

	foreach ($logs as $log) {
		unlink($log);
	}
}

function get_job_pid($log_number) {
	global $log_directory;
	$log_file = escapeshellarg(get_log_file($log_number));
	return trim(`head -n 1 $log_file | awk '{print \$2}'`);
}

function get_job_jobid($log_number) {
	global $log_directory;
	$log_file = escapeshellarg(get_log_file($log_number));
	return trim(`sed -n '2{p;q;}' $log_file | awk '{print \$2}'`);
}

function all_job_files_present($jobid) {
	global $autobuild_builds_directory;
	$build_files_directory = "$autobuild_builds_directory/$jobid/";
	$package_directories = array_filter(glob(pattern: "$build_files_directory/*"), 'is_dir');
	if (empty($package_directories)) {
		return false;
	}

	foreach ($package_directories as $package_directory) {
		$file_iterator = new FilesystemIterator($package_directory);

		// A failed build will generate only one file in the package directory: the package .build file
		// A successful build will generate multiple files, including of course the actual .deb package
		if (iterator_count($file_iterator) <= 1) {
			return false;
		}
	}

	return true;
}

function get_job_status($log_number) {
	global $log_directory;
	global $autobuild_builds_directory;
	$log_file = escapeshellarg(get_log_file($log_number));

	// Get the PID and the Job ID
	$pid = get_job_pid($log_number);
	$jobid = get_job_jobid($log_number);

	$logfile_last_line = trim(`tail -n 1 $log_file`);

	/***
	 *  3 bits:
	 * 		First bit:	Is the job still running?
	 * 		Second bit:	Did the job generate all the files we expected it to?
	 * 		Third bit:	Is the job either QUEUED or was it CANCELED?
	 * From this, here are the status codes:
	 * 	000: (Decimal 0)
	 * 		Job failed
	 * 	001: (Decimal 1)
	 * 		Job canceled
	 * 	010: (Decimal 2)
	 * 		Job completed successfully
	 * 	100: (Decimal 4)
	 * 		Job in progress
	 * 	101: (Decimal 5)
	 * 		Job queued
	 * We don't need to care about any other codes
	*/ 

	$in_progress		= 4	* file_exists("/proc/$pid");
	$files_present		= 2	* all_job_files_present($jobid);
	$queued_or_canceled	= 1	* (($logfile_last_line == "Queued") || ($logfile_last_line == "Canceled"));

	$status_code = $in_progress | $files_present | $queued_or_canceled;

	return $status_code;
}

function print_status_code($status_code, $html = false) {
	$label = "";
	$color = "000000";

	switch ($status_code) {
		case 0:
			$label = "Failed";
			$color = "FF0000";
			break;
		case 1:
			$label = "Canceled";
			$color = "FF0000";
			break;
		case 2:
			$label = "Successful";
			$color = "00FF00";
			break;
		case 4:
			$label = "In progress";
			$color = "0000FF";
			break;
		case 5:
			$label = "Queued";
			$color = "0000FF";
			break;
	}

	if ($html) {
		$label = "<font color=\"#$color\">$label</font>";
	}
	
	return $label;
}

function get_download_links($log_number) {
	global $log_directory;
	global $autobuild_builds_directory;

	$jobid = get_job_jobid($log_number);

	$build_files_directory = "$autobuild_builds_directory/$jobid";
	$package_directories = array_filter(glob(pattern: "$build_files_directory/*"), 'is_dir');

	$package_debs = array();

	foreach ($package_directories as $package_directory) {
		foreach (glob("$package_directory/*.deb") as $deb) {
			$deb = str_replace($autobuild_builds_directory, "", $deb);
			$path_components = explode("/", $deb);
			$download_link = "download.php?jobid=".$path_components[1]."&pkg=".$path_components[2]."&file=".$path_components[3];
			$package_debs[$path_components[3]] = $download_link;
		}
	}

	return $package_debs;
}
