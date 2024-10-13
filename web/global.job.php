<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

$socket_location = "unix:///var/run/autobuild.socket";

/* Functions to run the autobuild daemon */
function run_autobuild($command) {
	global $socket_location;
	$socket = stream_socket_client($socket_location);

	fwrite($socket, $command);
	fclose($socket);
}

function run_autobuild_and_get_jobid($command) {
	global $socket_location;
	$socket = stream_socket_client($socket_location);

	fwrite($socket, $command);
	fgets($socket); // The first line of output is the PID
	$job_id = fgets($socket); // The second line of output is the JOBID
	fclose($socket);

	$job_id = trim(str_replace("JOBID: ", "", $job_id));

	return $job_id;
}

function run_autobuild_and_wait_for_finish($command) {
	`autobuild $command`;
}
