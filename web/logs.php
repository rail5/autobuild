<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

$page = 1;
$logs_per_page = 10;

if (isset($_GET["page"]) && is_numeric($_GET["page"])) {
	$page = intval($_GET["page"]);
}

if (isset($_GET["logs-per-page"]) && is_numeric($_GET["logs-per-page"])) {
	$logs_per_page = intval($_GET["logs-per-page"]);
}

/* Are we deleting logs? */
if (isset($_GET["delete-all"]) && !isset($_GET["error"])) {
	delete_all_logs();
} else if (isset($_GET["delete-all-on-page"]) && !isset($_GET["error"])) {
	$build_logs = get_build_logs(($page - 1) * $logs_per_page, $logs_per_page);
	foreach ($build_logs as $log_file) {
		$log_number = str_replace(".log", "", basename($log_file));
		delete_log($log_number);
	}
} else if (isset($_GET["delete"]) && !isset($_GET["error"])) {
	foreach ($_GET["delete"] as $log_number) {
		delete_log($log_number);
	}
}

$build_logs = get_build_logs(($page - 1) * $logs_per_page, $logs_per_page);

$job_ids = array();

foreach ($build_logs as $log_file) {
	// Get the Job ID
	$log_file_head = file($log_file);
	$job_ids[trim(explode(" ", $log_file_head[1])[1])] = $log_file;
}

unset($build_logs);
krsort($job_ids);

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
					<div class="card" id="logs">
						<div style="width: 100%; overflow: hidden;">
							<h2>Build Logs</h2>
							<form action="logs.php" method="get" style="display: inline-block;">
								<input type="hidden" name="page" value="<?php echo $page; ?>">
								<input type="hidden" name="logs-per-page" value="<?php echo $logs_per_page; ?>">
								<button type="submit" class="no-decoration">Delete selected logs</button> &nbsp; <a href="logs.php?delete-all=true" class="button-no-decoration">Delete all logs</a>
								<br><br>
								<input type="checkbox" class="unhide" name="delete-all-on-page" value="true"> Select all
								<br>
							<?php
								$all_selected = "";
								$none_selected = "";
								foreach ($job_ids as $job_id => $log_file) {
									$log_number = str_replace(".log", "", basename($log_file));
									$none_selected .= "<input type=\"checkbox\" name=\"delete[]\" value=\"$log_number\"> <a href=\"log.php?log=$log_number\">$job_id</a><br>".PHP_EOL;
									$all_selected .= "<input type=\"checkbox\" name=\"delete[]\" value=\"$log_number\" checked disabled> <a href=\"log.php?log=$log_number\">$job_id</a><br>".PHP_EOL;
								}
								echo "
								<div class=\"hidden\">".PHP_EOL.$all_selected."
								</div>".PHP_EOL;
								echo "
								<div class=\"shown\">".PHP_EOL.$none_selected."
								</div>".PHP_EOL;
							?>
							</form>

							<!-- List pagination -->
							<?php
								$logs_count = get_number_of_build_logs();
								$pages = ceil($logs_count / $logs_per_page);
								$prev_page = $page - 1;
								$next_page = $page + 1;
								$show_prev = $prev_page >= 1;
								$show_next = $next_page <= $pages;

								if ($show_prev || $show_next) {
									echo "<br><br>Page: ";
									if ($show_prev) {
										echo "<a href=\"logs.php?page=$prev_page&logs-per-page=$logs_per_page\">&lt;</a> ";
									} else {
										echo "&lt; ";
									}
									for ($i = 1; $i <= $pages; $i++) {
										if ($i == $page) {
											echo "<b>$i</b> ";
										} else {
											echo "<a href=\"logs.php?page=$i&logs-per-page=$logs_per_page\">$i</a> ";
										}
									}
									if ($show_next) {
										echo "<a href=\"logs.php?page=$next_page&logs-per-page=$logs_per_page\">&gt;</a>";
									} else {
										echo "&gt;";
									}
								}
							?>
							<br><br>
							<form action="logs.php" method="get" style="display: inline-block;">
							<input type="hidden" name="page" value="<?php echo $page; ?>">
							Logs per page:
							<select name="logs-per-page" id="logs-per-page" class="small">
								<?php
									for ($i = 10; $i <= 100; $i += 10) {
										$selected = $i == $logs_per_page ? " selected" : "";
										echo "<option value=\"$i\"$selected>$i</option>";
									}
								?>

							</select>
							&nbsp;
							<button type="submit" class="no-decoration">Apply</button>
							</form>
						</div>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>