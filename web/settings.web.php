<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GPL 3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

/* Autobuild-web Settings */

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
						<h3>Old Builds</h3>
						<form action="settings.web.php" method="get">
							<input type="hidden" name="submitted" value="true">
							<input type="hidden" name="action" value="update-cron">
							<li><input type="checkbox" name="auto-clear-builds" id="auto-clear-builds"> <label for="auto-clear-builds">Auto-delete <u>builds</u> older than</label>
								<input type="number" name="auto-clear-builds-length" class="inline">
								<select name="auto-clear-builds-length-unit" class="inline">
									<option value="seconds">Seconds</option>
									<option value="minutes">Minutes</option>
									<option value="hours">Hours</option>
									<option value="days">Days</option>
								</select> 
							</li>
							<br>
							<h3>Old Logs</h3>
							<li><input type="checkbox" name="auto-clear-logs" id="auto-clear-logs"> <label for="auto-clear-logs">Auto-delete <u>logs</u> older than</label>
								<input type="number" name="auto-clear-logs-length" class="inline">
								<select name="auto-clear-logs-length-unit" class="inline">
									<option value="seconds">Seconds</option>
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