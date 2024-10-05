<?php
$socket_location = "unix:///var/run/autobuild.socket";

/* Functions to run the autobuild daemon */
function run_autobuild($command) {
	global $socket_location;
	$socket = stream_socket_client($socket_location);

	fwrite($socket, $command);
	fclose($socket);
}
