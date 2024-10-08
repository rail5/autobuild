<?php
require_once "global.php";

$settings = parse_config();

$debian_repos = get_debian_repos();

$debian_repos_configured = !empty($debian_repos);
$github_configured = github_is_configured($settings);
$forgejo_configured = forgejo_is_configured($settings);

$amd64_configured = vm_is_configured("amd64");
$i386_configured = vm_is_configured("i386");
$arm64_configured = vm_is_configured("arm64");

if (isset($_GET['submitted']) && !isset($_GET['error'])) {
	/* Form submitted */

	// Do we have everything we need?
	if (!isset($_GET["pkg"])) {
		$_GET["error"] = "no-pkg";
		redirect_and_die("build.php", $_GET);
	}

	if (!isset($_GET["arch"])) {
		$_GET["error"] = "no-arch";
		redirect_and_die("build.php", $_GET);
	}

	$packages = array(); // Packages we want to build
	$architectures = array(); // Architectures we want to build them for
	$distribution_channels = array(); // Places we want to distribute those packages
	$repos_to_push = array(); // Debian repos we want to push them to (if pushing to debian repos)
	$upgrade_vms = isset($_GET["upgrade"]); // Do we want to upgrade the VMs before building?

	// First, validate that all of the packages are alright
	$acceptable_packages = $settings["packages"]["package_urls"];
	for ($i = 0; $i < count($acceptable_packages); $i++) {
		$acceptable_packages[$i] = str_replace(".git", "", basename($acceptable_packages[$i]));
	}

	/* Parse options */

	// Packages 
	foreach ($_GET['pkg'] as $package) {
		if (in_array($package, $acceptable_packages)) {
			$packages[] = $package;
		}
	}

	// Architectures
	foreach ($_GET["arch"] as $architecture) {
		$valid_arch = false;
		switch ($architecture) {
			case "amd64":
				$valid_arch = $amd64_configured;
				break;
			case "i386":
				$valid_arch = $i386_configured;
				break;
			case "arm64":
				$valid_arch = $arm64_configured;
				break;
		}

		if ($valid_arch && !in_array($architecture, $architectures)) {
			$architectures[] = $architecture;
		}
	}

	// Distribution Channels
	foreach ($_GET["dist"] as $distribution_channel) {
		$valid_channel = false;
		switch ($distribution_channel) {
			case "debian-repo":
				$valid_channel = true;
				break;
			case "github":
				$valid_channel = true;
				break;
			case "forgejo":
				$valid_channel = true;
				break;
		}

		if ($valid_channel && !in_array($distribution_channel, $distribution_channels)) {
			$distribution_channels[] = $distribution_channel;
		}
	}

	// If we're distributing to Debian repos, which ones?
	if (in_array("debian-repo", $distribution_channels)) {
		foreach ($_GET["repo"] as $repository) {
			if (in_array($repository, $debian_repos) && !in_array($repository, $repos_to_push)) {
				$repos_to_push[] = $repository;
			}
		}
	}

	/* Generate build command */
	$build_command = "";

	foreach ($packages as $package) {
		$build_command .= "-p \"$package\" ";
	}

	foreach ($architectures as $arch) {
		$build_command .= "--$arch ";
	}

	foreach ($distribution_channels as $distribution_channel) {
		switch ($distribution_channel) {
			case "github":
				$build_command .= "-g ";
				break;
			case "forgejo":
				$build_command .= "-f ";
				break;
			case "debian-repo":
				foreach ($repos_to_push as $repository) {
					$build_command .= "-d \"$repository\" ";
				}
				break;
		}
	}

	if (!$upgrade_vms) {
		$build_command .= "-n ";
	}

	// Create log file
	$log_file = create_log_file();
	$log_number = str_replace(".log", "", basename($log_file));
	$build_command .= "-L \"$log_file\" ";

	/* Run build command */
	run_autobuild($build_command);

	while (filesize($log_file) === 0) {
		sleep(1); // Sleep until we can get the job info
		clearstatcache(); // Don't cache the result of filesize()
	}

	// Get the PID and the Job ID
	$autobuild_pid = get_job_pid($log_number);
	$autobuild_jobid = get_job_jobid($log_number);

	$timestamp = str_replace(".$autobuild_pid", "", $autobuild_jobid);
	$timestamp = str_replace(".", " ", $timestamp);
	$timestamp = preg_replace('/[^0-9\-\ :(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)]/', "", $timestamp);

	add_build($timestamp);

	// Redirect to watch page
	header("location: log.php?log=$log_number");
	die();
}

$debian_checkbox_enabled	= $debian_repos_configured	? "" : " disabled";
$github_checkbox_enabled	= $github_configured		? "" : " disabled";
$forgejo_checkbox_enabled	= $forgejo_configured		? "" : " disabled";

$amd64_checkbox_enabled	= $amd64_configured	? "" : " disabled";
$i386_checkbox_enabled	= $i386_configured	? "" : " disabled";
$arm64_checkbox_enabled	= $arm64_configured	? "" : " disabled";

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
						<h2>Start New Build</h2>
						<form action="build.php" method="get">
							<h3><u>Packages</u></h3>
							<div class="checkbox-list">
<?php
	foreach ($settings["packages"]["package_urls"] as $index => $url ) {
		$package_name = str_replace(".git", "", basename($url));
		$pkg_checkbox_onoff = isset($_GET["pkg"]) && in_array($package_name, $_GET["pkg"]) ? " checked" : "";
		echo "<li><input type=\"checkbox\" id=\"$package_name\" name=\"pkg[]\" value=\"$package_name\"$pkg_checkbox_onoff>";
		echo "<label for=\"$package_name\"> $package_name</label></li>".PHP_EOL;
	}

$amd64_checkbox_onoff = isset($_GET["arch"]) && in_array("amd64", $_GET["arch"]) ? " checked":"";
$i386_checkbox_onoff = isset($_GET["arch"]) && in_array("i386", $_GET["arch"]) ? " checked":"";
$arm64_checkbox_onoff = isset($_GET["arch"]) && in_array("arm64", $_GET["arch"]) ? " checked":"";
?>
							</div>
							<h3><u>Target Architectures</u></h3>
							<div class="checkbox-list">
								<li>
									<input type="checkbox" id="amd64" name="arch[]" value="amd64"<?php echo $amd64_checkbox_onoff.$amd64_checkbox_enabled; ?>>
									<label for="amd64">amd64</label>
								</li>
								<li>
									<input type="checkbox" id="i386" name="arch[]" value="i386"<?php echo $i386_checkbox_onoff.$i386_checkbox_enabled; ?>>
									<label for="i386">i386</label>
								</li>
								<li>
									<input type="checkbox" id="arm64" name="arch[]" value="arm64"<?php echo $arm64_checkbox_onoff.$arm64_checkbox_enabled; ?>>
									<label for="arm64">arm64</label>
								</li>
							</div>

							<h3><u>Distribution Channels</u></h3>
							
									<div class="two-by-two">
										<input type="checkbox" id="debian-repo" name="dist[]" value="debian-repo" class="unhide"<?php echo $debian_checkbox_enabled; ?>>
										<label for="debian-repo">Debian Repositories</label>
										<div class="hidden">
											<br>
<?php
	foreach ($debian_repos as $repo_name ) {
		echo "<li><input type=\"checkbox\" id=\"$repo_name\" name=\"repo[]\" value=\"$repo_name\">";
		echo "<label for=\"$repo_name\"> $repo_name</label></li>".PHP_EOL;
	}
?>
										</div>
									</div>
								<li>
									<input type="checkbox" id="github" name="dist[]" value="github"<?php echo $github_checkbox_enabled ?>>
									<label for="github">GitHub Release Pages</label>
								</li>
								<li>
									<input type="checkbox" id="forgejo" name="dist[]" value="forgejo"<?php echo $forgejo_checkbox_enabled ?>>
									<label for="forgejo">Forgejo Release Pages</label>
								</li>
							
							<h3><u>Options</u></h3>
							<li>
								<input type="checkbox" id="upgrade" name="upgrade" value="1" checked>
								<label for="upgrade">Upgrade Build Farm VMs before building</label>
							</li>

							<input type="hidden" name="submitted" value="true">

							<button type="submit">Start Build</button>
						</form>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>