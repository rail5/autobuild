<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

/* Display functions (template html) */
function display_error_message() {
	if (isset($_GET["error"])) {
		$error_message = "Error";
		switch ($_GET["error"]) {
			case "no-pkg":
				$error_message = "No packages selected";
				break;
			case "no-arch":
				$error_message = "No build architectures selected";
				break;
			case "invalid-arch":
				$error_message = "Invalid build architecture selected";
				break;
			case "no-log":
				$error_message = "No log selected";
				break;
			case "invalid-log":
				$error_message = "Invalid log selection";
				break;
			case "invalid-file":
				$error_message = "Invalid file requested";
				break;
			case "password-mismatch":
				$error_message = "Passwords do not match";
				break;
			case "invalid-captcha":
				$error_message = "Invalid captcha";
				break;
			case "form-incomplete":
				$error_message = "Please fill out all fields";
				break;
			case "db-error":
				$error_message = "Could not contact database";
				break;
			case "invalid-credentials":
				$error_message = "Incorrect username or password";
				break;
			case "no-repo-name":
				$error_message = "Provide a repository name";
				break;
			case "no-repo-url":
				$error_message = "Provide a repository URL";
				break;
			case "invalid-repo":
				$error_message = "Invalid repository";
				break;
			case "invalid-repo-name":
				$error_message = "Invalid repository name";
				break;
			case "invalid-repo-url":
				$error_message = "Invalid repository URL";
				break;
			case "repo-exists":
				$error_message = "Repository already exists";
				break;
			case "key-exists":
				$error_message = "Signing key already exists";
				break;
			case "invalid-email":
				$error_message = "Invalid email address";
				break;
			case "invalid-github-pages-url":
				$error_message = "Invalid GitHub Pages URL";
				break;
			case "github-not-configured":
				$error_message = "Your GitHub credentials are not configured";
				break;
			case "no-action":
				$error_message = "No action specified";
				break;
			case "invalid-action":
				$error_message = "Invalid action specified";
				break;
		}
		echo PHP_EOL.'<div align="center" width="50%" height="50%" class="error-message">Error: '.$error_message.'</div>'.PHP_EOL;
	}
}

function display_note() {
	if (isset($_GET["note"])) {
		$note_message = "Note";
		switch ($_GET["note"]) {
			case "account-updated":
				$note_message = "Account updated";
				break;
			case "key-added":
				$note_message = "Signing key added";
				break;
			case "key-removed":
				$note_message = "Signing key removed";
				break;
			case "installing-vm":
				$note_message = "Installing VMs";
				break;
			case "upgrading-vm":
				$note_message = "Upgrading VMs";
				break;
			case "removed-vm":
				$note_message = "Removed VMs";
				break;
		}
		echo PHP_EOL.'<div align="center" width="50%" height="50%" class="note-message">Note: '.$note_message.'</div>'.PHP_EOL;
	}
}

function display_header() {
	echo '<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Autobuild</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
	<header>
		<div class="container">
			<div class="header-content">
				<div class="logo"><a href="index.php" class="no-highlight">Autobuild</a></div>
				<nav>
					<ul>
						<li><a href="index.php">Dashboard</a></li>
						<li><a href="build.php">Build</a></li>
						<li><a href="logs.php">Logs</a></li>
						<li><a href="settings.php">Settings</a></li>
						<li><a href="repositories.php">Repositories</a></li>
						';
	if ($_SESSION['logged-in']) {
		echo '<li><a href="logout.php">Logout</a></li>';
	}
	echo '
					</ul>
				</nav>
			</div>
		</div>
	</header>';

	if (!is_secure()) {
		echo PHP_EOL.'<div align="center" width="50%" height="50%" class="warning-message">Warning: Your connection is not secure</div>'.PHP_EOL;
	}
}

function display_sidebar_actions() {
	echo '
					<div class="card sidebar">
						<h2>Quick Actions</h2>
						<ul>
							<li><a href="build.php">Start New Build</a></li>
							<li><a href="repositories.php">Manage Repositories</a></li>
							<li><a href="settings.farm.php">Configure Build Farm</a></li>
						</ul>
					</div>';
}

function display_sidebar_statistics() {
	$build_stats = get_build_stats();
	$latest_build		=	$build_stats['latest_build'];
	$total_builds		=	$build_stats['builds'];
	echo "
					<div class=\"card sidebar\">
						<h2>Build Statistics</h2>
						<p>Total Builds: $total_builds</p>
						<p>Last Build: $latest_build</p>
					</div>";
}
