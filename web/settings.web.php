<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

/* Autobuild-web Settings */

if (isset($_GET["action"]) && !isset($_GET["error"])) {
	// Form submitted
	if (is_array($_GET["action"])) {
		redirect_and_die("self");
	}

	switch ($_GET["action"]) {
		case "clear-builds":
			run_autobuild("-r all");
			redirect_and_die("settings.web.php");
			break;
		case "clear-logs":
			delete_all_logs();
			redirect_and_die("settings.web.php");
			break;
		case "clear-stats":
			clear_build_stats();
			redirect_and_die("settings.web.php");
			break;
		case "update-cron":
			$auto_clear_builds = isset($_GET["auto-clear-builds"]) ? "1" : "0";
			$auto_clear_logs = isset($_GET["auto-clear-logs"]) ? "1" : "0";
			$auto_upgrade_vms = isset($_GET["auto-upgrade-vms"]) ? "1" : "0";

			$auto_clear_builds_minutes = $_GET["auto-clear-builds-length"];
			$auto_clear_logs_minutes = $_GET["auto-clear-logs-length"];
			$auto_upgrade_vms_minutes = $_GET["auto-upgrade-vms-cycle"];

			if (!is_numeric($auto_clear_builds_minutes) || !is_numeric($auto_clear_logs_minutes) || !is_numeric($auto_upgrade_vms_minutes)) {
				redirect_and_die("settings.web.php");
			}

			$new_cron_settings = array();
			$new_cron_settings["auto_clear_builds"]			= intval($auto_clear_builds);
			$new_cron_settings["auto_clear_logs"]			= intval($auto_clear_logs);
			$new_cron_settings["auto_upgrade_vms"]			= intval($auto_upgrade_vms);
			$new_cron_settings["auto_clear_builds_minutes"]	= intval($auto_clear_builds_minutes);
			$new_cron_settings["auto_clear_logs_minutes"]	= intval($auto_clear_logs_minutes);
			$new_cron_settings["auto_upgrade_vms_minutes"]	= intval($auto_upgrade_vms_minutes);

			update_cron_settings($new_cron_settings);

			redirect_and_die("settings.web.php");
			break;
	}
}

$cron_settings = get_cron_settings();

$auto_clear_builds_checked	= $cron_settings["auto_clear_builds"] == "1" ? " checked" : "";
$auto_clear_logs_checked	= $cron_settings["auto_clear_logs"] == "1" ? " checked" : "";
$auto_upgrade_vms_checked	= $cron_settings["auto_upgrade_vms"] == "1" ? " checked" : "";

$auto_clear_builds_minutes	= $cron_settings["auto_clear_builds_minutes"];
$auto_clear_logs_minutes	= $cron_settings["auto_clear_logs_minutes"];
$auto_upgrade_vms_minutes	= $cron_settings["auto_upgrade_vms_minutes"];

display_header();
display_error_message();
?>

	<main>
		<div class="container">
			<div class="content-wrapper">
                <aside class="sidebar">
				    <?php
                    display_sidebar_actions();
                    display_sidebar_statistics();
                    ?>
                </aside>
				<section class="main-content">
					<div class="card" id="general">
						<h2>General</h2>
						<div class="two-by-two">
						<a class="button" href="?action=clear-builds">Clear Builds Folder</a> &nbsp; <a class="button" href="?action=clear-logs">Clear Logs Folder</a> &nbsp; <a class="button" href="?action=clear-stats">Clear Build Statistics</a>
						</div>
						<br><br>
						<form action="settings.web.php" method="get">
						<h3>Old Builds</h3>
							<input type="hidden" name="submitted" value="true">
							<input type="hidden" name="action" value="update-cron">
							<li><input type="checkbox" name="auto-clear-builds" id="auto-clear-builds"<?php echo $auto_clear_builds_checked; ?>> <label for="auto-clear-builds">Auto-delete <u>builds</u> older than &nbsp; </label>
								<input type="number" name="auto-clear-builds-length" class="inline" min="1" value="<?php echo $auto_clear_builds_minutes; ?>">
								<select name="auto-clear-builds-length-unit" class="inline">
									<option value="minutes">Minutes</option>
									<option value="hours">Hours</option>
									<option value="days">Days</option>
								</select> 
							</li>
							<br>
							<h3>Old Logs</h3>
							<li><input type="checkbox" name="auto-clear-logs" id="auto-clear-logs"<?php echo $auto_clear_logs_checked; ?>> <label for="auto-clear-logs">Auto-delete <u>logs</u> older than &nbsp; </label>
								<input type="number" name="auto-clear-logs-length" class="inline" min="1" value="<?php echo $auto_clear_logs_minutes; ?>">
								<select name="auto-clear-logs-length-unit" class="inline">
									<option value="minutes">Minutes</option>
									<option value="hours">Hours</option>
									<option value="days">Days</option>
								</select>
							</li>
							<br><br>
							<h3>Build Farm</h3>
							<li><input type="checkbox" name="auto-upgrade-vms" id="auto-upgrade-vms"<?php echo $auto_upgrade_vms_checked; ?>> <label for="auto-upgrade-vms">Auto-upgrade VMs every &nbsp; </label>
								<input type="number" name="auto-upgrade-vms-cycle" class="inline" min="1" value="<?php echo $auto_upgrade_vms_minutes; ?>">
								<select name="auto-upgrade-vms-cycle-unit" class="inline">
									<option value="minutes">Minutes</option>
									<option value="hours">Hours</option>
									<option value="days">Days</option>
								</select>
							</li>
							<br><br>
							<button type="submit" name="update-cron">Save Changes</button>
						</form>
					</div>

					<div class="card" id="account">
						<h2>Username/Password</h2>
						<form action="settings.web.php" method="post">
							<input type="hidden" name="submitted" value="true">
							<input type="hidden" name="action" value="update-account">
							<label for="username">Username: </label>
							<input type="text" name="username" placeholder="Username" value="<?php echo get_username(); ?>">
							<br>
							<label for="current-password">Current Password: </label>
							<input type="password" name="current-password" placeholder="Current Password">
							<br>
							<br>
							<label for="password">New Password: </label>
							<input type="password" name="password" placeholder="New Password">
							<br>
							<label for="password-confirm">Confirm New Password: </label>
							<input type="password" name="password-confirm" placeholder="Confirm New Password">
							<br>
							<button type="submit" name="change-credentials">Save Changes</button>
						</form>
						</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>