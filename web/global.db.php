<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

$db_file = "/var/autobuild/web/db.sqlite";

$db = new SQLite3($db_file) or die("Cannot access SQLite database file");

$db->exec('CREATE TABLE IF NOT EXISTS "data" (
	"uid"	INTEGER NOT NULL UNIQUE,
	"username"	TEXT NOT NULL,
	"password"	TEXT NOT NULL,
	"latest_build"	TEXT NOT NULL DEFAULT "Never",
	"builds"	INTEGER NOT NULL DEFAULT 0,
	"auto_clear_builds"	INTEGER NOT NULL DEFAULT 0,
	"auto_clear_builds_minutes"	INTEGER NOT NULL DEFAULT 0,
	"auto_clear_logs"	INTEGER NOT NULL DEFAULT 0,
	"auto_clear_logs_minutes"	INTEGER NOT NULL DEFAULT 0,
	"auto_upgrade_vms"	INTEGER NOT NULL DEFAULT 0,
	"auto_upgrade_vms_minutes"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("uid" AUTOINCREMENT)
);');

bring_db_structure_up_to_date();

$username = $db->query('SELECT username FROM "data" WHERE uid=1')->fetchArray();

if (!$username && basename($_SERVER["PHP_SELF"]) != "setup.php") {
	header('location: setup.php');
	die();
}

if ($username && basename($_SERVER["PHP_SELF"]) == "setup.php") {
	header('location: index.php');
	die();
}

$acceptable_guest_pages = array(
	"login.php",
	"captcha.php",
	"view-captcha.php"
);

if ($username && !$_SESSION['logged-in'] && !in_array(basename($_SERVER["PHP_SELF"]), $acceptable_guest_pages)) {
	header('location: login.php');
	die();
}


/* Database functions */
function bring_db_structure_up_to_date() {
	global $db;

	// Fields that were not included in the initial release of autobuild-web
	$columns = array(
		"auto_clear_builds",
		"auto_clear_builds_minutes",
		"auto_clear_logs",
		"auto_clear_logs_minutes",
		"auto_upgrade_vms",
		"auto_upgrade_vms_minutes"
	);

	foreach ($columns as $column) {
		$test_query = "SELECT INSTR(sql, '$column') FROM sqlite_master WHERE type='table' AND name='data'";
		$test_result = $db->query($test_query)->fetchArray();
		
		if ($test_result[0] == 0) {
			try {
				$db->exec("ALTER TABLE data ADD COLUMN $column INTEGER NOT NULL DEFAULT 0;");
			} catch (Exception $e) {
				// Do nothing
			}
		}
	}
}

function create_user($username, $password) {
	global $db;
	$query = $db->prepare('INSERT INTO data (username, password) VALUES (:user, :pass)');
	$query->bindValue(':user', $username);
	$query->bindValue(':pass', $password);
	
	return $query->execute();
}

function get_username() {
	global $db;
	return $db->query('SELECT username FROM "data" WHERE uid=1')->fetchArray()[0];
}

function update_user($username, $password) {
	global $db;
	
	$query = $db->prepare('UPDATE data SET username = :user, password = :pass WHERE uid=1');
	$query->bindValue(':user', $username);
	$query->bindValue(':pass', password_hash($password, PASSWORD_DEFAULT));
	return $query->execute();
}

function log_in($username, $password) {
	global $db;
	$credentials = $db->query('SELECT username, password FROM "data" WHERE uid=1')->fetchArray();

	return $username == $credentials['username'] && password_verify($password, $credentials['password']);
}

function get_build_stats() {
	global $db;
	return $db->query('SELECT latest_build, builds FROM "data" WHERE uid=1')->fetchArray();
}

function clear_build_stats() {
	global $db;
	return $db->query('UPDATE data SET latest_build = "Never", builds = 0 WHERE uid=1')->fetchArray();
}

function get_cron_settings() {
	global $db;
	return $db->query('SELECT auto_clear_builds, auto_clear_builds_minutes, auto_clear_logs, auto_clear_logs_minutes, auto_upgrade_vms, auto_upgrade_vms_minutes FROM "data" WHERE uid=1')->fetchArray();
}

function update_cron_settings($new_cron) {
	global $db;

	// Make sure the input values make sense
	if ($new_cron['auto_clear_builds'] != 1) {
		$new_cron['auto_clear_builds'] = 0;
		$new_cron['auto_clear_builds_minutes'] = 0;
	}

	if ($new_cron['auto_clear_logs'] != 1) {
		$new_cron['auto_clear_logs'] = 0;
		$new_cron['auto_clear_logs_minutes'] = 0;
	}

	if ($new_cron['auto_upgrade_vms'] != 1) {
		$new_cron['auto_upgrade_vms'] = 0;
		$new_cron['auto_upgrade_vms_minutes'] = 0;
	}

	if ($new_cron['auto_clear_builds'] == 1 && $new_cron['auto_clear_builds_minutes'] < 1) {
		$new_cron['auto_clear_builds_minutes'] = 1;
	}

	if ($new_cron['auto_clear_logs'] == 1 && $new_cron['auto_clear_logs_minutes'] < 1) {
		$new_cron['auto_clear_logs_minutes'] = 1;
	}

	if ($new_cron['auto_upgrade_vms'] == 1 && $new_cron['auto_upgrade_vms_minutes'] < 30) {
		$new_cron['auto_upgrade_vms_minutes'] = 30;
	}

	$query = $db->prepare('UPDATE data SET auto_clear_builds = :acb, auto_clear_builds_minutes = :acbm, auto_clear_logs = :acl, auto_clear_logs_minutes = :aclm, auto_upgrade_vms = :auv, auto_upgrade_vms_minutes = :auvm WHERE uid=1');
	$query->bindValue(':acb', $new_cron['auto_clear_builds']);
	$query->bindValue(':acbm', $new_cron['auto_clear_builds_minutes']);
	$query->bindValue(':acl', $new_cron['auto_clear_logs']);
	$query->bindValue(':aclm', $new_cron['auto_clear_logs_minutes']);
	$query->bindValue(':auv', $new_cron['auto_upgrade_vms']);
	$query->bindValue(':auvm', $new_cron['auto_upgrade_vms_minutes']);

	return $query->execute();
}

function add_build($date) {
	global $db;
	$query = $db->prepare('UPDATE data SET latest_build = :builddate, builds = builds + 1 WHERE uid=1');
	$query->bindValue(':builddate', $date);

	return $query->execute();
}
