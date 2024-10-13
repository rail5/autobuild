<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

$log_number = $_GET["log"];

if (!isset($_GET["error"])) {
	$log_file = get_log_file($log_number);

	while (filesize($log_file) === 0) {
		sleep(1); // Sleep until we can get the job info
		clearstatcache(); // Don't cache the result of filesize()
	}

	// Get the PID and the Job ID
	$autobuild_pid = get_job_pid($log_number);
	$autobuild_status = get_job_status($log_number);

	// Are we canceling the job?
	// If the user asked to cancel, first make sure that the job is actually running (first bit in the status code = 1)
	if (isset($_GET["cancel"]) && $autobuild_status & 4) {
		run_autobuild("-k $autobuild_pid");
	}

	// Get the individual package build logs
	$package_logs = get_package_build_log_names($log_number);

	$tab = "main";

	if (isset($_GET["tab"]) && in_array($_GET["tab"], $package_logs)) {
		$tab = $_GET["tab"];
	}
}

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
					<div class="card" id="new-build">
						<div style="width: 100%; overflow: hidden;">
							<div style="width: 50%; float: left;">
								<h2>Build Log</h2>
							</div>
							<?php
							if ($autobuild_status & 4) {
								echo '
							<div id="control-buttons" style="margin-left: calc(100% - 85px);">
								<a href="' . basename($_SERVER["PHP_SELF"]) . "?log=" . $_GET["log"] . '&cancel=true">
									<img src="img/cancel.webp" width="30px" height="30px" title="Cancel build" />
								</a>
								&nbsp; &nbsp; 
								<a href="' . basename($_SERVER["PHP_SELF"]) . "?log=" . $_GET["log"] . '&tab='. $tab .'">
									<img src="img/refresh.webp" width="30px" height="30px" title="Refresh log" />
								</a>
							</div>';
							}
							?>
						</div>
						<label for="build-log">Build: 
							<?php
								echo print_status_code($autobuild_status, true);
							?>
						</label>
						<br>
						<div class="tabs" id="tabs">
							<?php
								echo '<a href="' . basename($_SERVER["PHP_SELF"]) . "?log=" . $_GET["log"] . '&tab=main#tabs" class="tab' . ($tab == "main" ? " active" : "") . '">Main</a>';
								foreach ($package_logs as $package_log) {
									echo '<a href="' . basename($_SERVER["PHP_SELF"]) . "?log=" . $_GET["log"] . '&tab=' . $package_log . '#tabs" class="tab' . ($tab == $package_log ? " active" : "") . '">' . $package_log . '</a>';
								}
							?>
						</div>
						<div id="log">
						<?php

						if (!isset($_GET["error"])) {
							echo '	<iframe src="view-log.php?log='.$_GET["log"].'&tab='.$tab.'#end" title="Build log" height="400" width="100%" id="build-log-iframe"></iframe><br>';
						}
						?>
						</div>
						
					</div>
					<div class="card" id="new-build">
						<div style="width: 100%; overflow: hidden;">
							<h2>Files</h2>
							<?php
								if ($autobuild_status & 2) {
									foreach (get_download_links($log_number) as $file => $link) {
										echo "<a href=\"$link\">$file</a><br>";
									}
								}
							?>
						</div>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>