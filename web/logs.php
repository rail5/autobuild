<?php
require_once "global.php";

/* Are we deleting logs? */
if (isset($_GET["delete-all"])) {
	delete_all_logs();
} else if (isset($_GET["delete"])) {
	foreach ($_GET["delete"] as $log_number) {
		delete_log($log_number);
	}
}

$build_logs = get_build_logs();
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
								<button type="submit" class="no-decoration">Delete selected logs</button>
								<br><br>
								<input type="checkbox" class="unhide" name="delete-all" value="true"> Select all
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
						</div>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>