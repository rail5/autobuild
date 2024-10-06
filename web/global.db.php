<?php
$db_file = "/var/autobuild/web/db.sqlite";

$db = new SQLite3($db_file) or die("Cannot access SQLite database file");

$db->exec('CREATE TABLE IF NOT EXISTS "data" (
	"uid"	INTEGER NOT NULL UNIQUE,
	"username"	TEXT NOT NULL,
	"password"	TEXT NOT NULL,
	"latest_build"	TEXT NOT NULL DEFAULT "Never",
	"builds"	INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY("uid" AUTOINCREMENT)
);');

$username = $db->query('SELECT username FROM "data" WHERE uid=1')->fetchArray();

if (!$username && basename($_SERVER["PHP_SELF"]) != "setup.php") {
	header('location: setup.php');
	die();
}

if ($username && basename($_SERVER["PHP_SELF"]) == "setup.php") {
	header('location: index.php');
	die();
}

if ($username && !$_SESSION['logged-in'] && basename($_SERVER['PHP_SELF']) != "login.php") {
	header('location: login.php');
	die();
}


/* Database functions */
function create_user($username, $password) {
	global $db;
	$query = $db->prepare('INSERT INTO data (username, password) VALUES (:user, :pass)');
	$query->bindValue(':user', $username);
	$query->bindValue(':pass', $password);
	
	return $query->execute();
}

function update_user($username, $password) {
	global $db;
	// FIXME
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

function add_build($date) {
	global $db;
	$query = $db->prepare('UPDATE data SET latest_build = :builddate, builds = builds + 1 WHERE uid=1');
	$query->bindValue(':builddate', $date);

	return $query->execute();
}
