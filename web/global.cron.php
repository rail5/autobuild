<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

/* Cron jobs */

$cron_settings = get_cron_settings();

if ($cron_settings["auto_clear_builds"]) {
	$builds_to_clear = get_builds_to_clear($cron_settings["auto_clear_builds_minutes"]);
	foreach ($builds_to_clear as $build) {
		$build_arg = escapeshellarg($build);
		run_autobuild("-r $build_arg");
	}
}

if ($cron_settings["auto_clear_logs"]) {
	$logs_to_clear = get_logs_to_clear($cron_settings["auto_clear_logs_minutes"]);
	foreach ($logs_to_clear as $log) {
		delete_log($log);
	}
}

if ($cron_settings["auto_upgrade_vms"]) {
	$upgrades_to_run = get_upgrades_to_run($cron_settings["auto_upgrade_vms_minutes"]);
	vm_upgrade($upgrades_to_run, false);
}
