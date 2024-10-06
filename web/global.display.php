<?php

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
			case "form-incomplete":
				$error_message = "Please fill out all fields";
				break;
			case "db-error":
				$error_message = "Could not contact database";
				break;
			case "invalid-credentials":
				$error_message = "Incorrect username or password";
				break;
		}
		echo PHP_EOL.'<div align="center" width="50%" height="50%" class="error-message">Error: '.$error_message.'</div>'.PHP_EOL;
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
							<li><a href="logs.php">Check Build Status</a></li>
							<li><a href="repositories.php">Manage Repositories</a></li>
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