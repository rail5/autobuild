<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

/* Logging & log file functions */

$log_directory = "/var/autobuild/web/log";
$autobuild_directory = "/var/autobuild";
$autobuild_repos_directory = "$autobuild_directory/repo";
$autobuild_builds_directory = "$autobuild_directory/builds";

function get_build_logs($offset = 0, $limit = null) {
	global $log_directory;
	$log_list = array_filter(glob("$log_directory/*.log"), 'file_not_empty');

	$log_list = array_slice($log_list, $offset, $limit);

	return $log_list;
}

function get_number_of_build_logs() {
	global $log_directory;
	$log_list = array_filter(glob("$log_directory/*.log"), 'file_not_empty');

	return count($log_list);
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

function get_package_build_logs($log_number) {
	global $log_directory;
	$package_log_list = glob("$log_directory/$log_number.*.build");

	$package_logs = array();

	foreach ($package_log_list as $package_log) {
		$log_name = basename($package_log, ".build");
		$log_name = str_replace("$log_number.", "", $log_name);
		$package_logs[$log_name] = $package_log;
	}

	return $package_logs;
}

function get_package_build_log_names($log_number) {
	$package_log_list = get_package_build_logs($log_number);

	$package_log_names = array();

	foreach ($package_log_list as $package_log) {
		$log_name = basename($package_log, ".build");
		$log_name = str_replace("$log_number.", "", $log_name);
		$package_log_names[] = $log_name;
	}

	return $package_log_names;
}

function get_package_build_log($log_number, $package) {
	$package_log_list = get_package_build_logs($log_number);

	foreach ($package_log_list as $name => $package_log) {
		if ($name == $package) {
			return $package_log;
		}
	}

	return "";
}

function delete_log($log_number) {
	global $log_directory;
	$log_file = get_log_file($log_number);
	$status_file = get_status_file($log_number);
	$package_log_files = get_package_build_logs($log_number);

	$jobid = escapeshellarg(get_job_jobid($log_number));

	unlink($log_file);
	unlink($status_file);

	foreach ($package_log_files as $package_log_file) {
		unlink($package_log_file);
	}

	run_autobuild("-r $jobid");
}

function get_status_file($log_number) {
	global $log_directory;
	$status_file = "$log_directory/$log_number.status";

	return $status_file;
}

function write_status_file($log_number, $status_code) {
	$status_file = get_status_file($log_number);
	file_put_contents($status_file, $status_code);

	// Make sure the autobuild user can read/write the status file
	chmod($status_file, 0770);

	return $status_file;
}

function delete_all_logs() {
	global $log_directory;
	$logs = array_filter(glob("$log_directory/*.{log,status,build}", GLOB_BRACE));

	foreach ($logs as $log) {
		unlink($log);
	}

	run_autobuild("-r all");
}

function get_logs_to_clear($older_than) {
	global $log_directory;
	$logs = array_filter(glob("$log_directory/*.log"), 'file_not_empty');

	$logs_to_clear = array();

	foreach ($logs as $log) {
		$log_number = basename($log, ".log");
		$timestamp = filemtime($log);

		if ((time() - $timestamp) > ($older_than * 60) && get_job_status($log_number) < 4) {
			$logs_to_clear[] = $log_number;
		}
	}

	return $logs_to_clear;
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

function get_job_status($log_number) {
	global $log_directory;
	global $autobuild_builds_directory;

	/***
	 *  3 bits:
	 * 		First bit:	Is the job still running?
	 * 		Second bit:	Did autobuild report success?
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

	if (file_exists(get_status_file($log_number))) {
		return intval(file_get_contents(get_status_file($log_number)));
	}

	// Get the PID and the Job ID
	$pid = get_job_pid($log_number);

	$log_file = escapeshellarg(get_log_file($log_number));
	$logfile_last_line = trim(`tail -n 1 $log_file`);


	$in_progress		= 4	* file_exists("/proc/$pid");
	$reported_success	= 2	* ($logfile_last_line == "Success");
	$queued_or_canceled	= 1	* (($logfile_last_line == "Queued") || ($logfile_last_line == "Canceled"));

	$status_code = $in_progress | $reported_success | $queued_or_canceled;

	if (($status_code & 4) == 0) {
		// Job is not running anymore -- write a status file
		write_status_file($log_number, $status_code);
	}

	return $status_code;
}

function get_builds_to_clear($older_than) {
	global $autobuild_builds_directory;
	$builds = array_filter(glob("$autobuild_builds_directory/*"), 'is_dir');

	$builds_to_clear = array();

	foreach ($builds as $build) {
		$timestamp = filemtime("$build/.");

		if ((time() - $timestamp) > ($older_than * 60)) {
			$builds_to_clear[] = basename($build);
		}
	}

	return $builds_to_clear;
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
