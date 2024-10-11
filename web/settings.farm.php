<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

/* Build Farm Settings */

$amd64_configured = vm_is_configured("amd64");
$i386_configured = vm_is_configured("i386");
$arm64_configured = vm_is_configured("arm64");

$amd64_installing = vm_is_installing("amd64");
$i386_installing = vm_is_installing("i386");
$arm64_installing = vm_is_installing("arm64");

$amd64_upgrading = vm_is_upgrading("amd64");
$i386_upgrading = vm_is_upgrading("i386");
$arm64_upgrading = vm_is_upgrading("arm64");

if (isset($_GET["submitted"]) && !isset($_GET["error"])) {
	// Form submitted

	// Do we have everything we need?
	if (!isset($_GET["arch"])) {
		redirect_and_die("settings.farm.php", array("error" => "no-arch"));
	}

	if (!isset($_GET["action"])) {
		$_GET["error"] = "no-action";
		redirect_and_die("settings.farm.php", $_GET);
	}

	$arch_list = $_GET["arch"];
	$action = $_GET["action"];

	// Check if the action is valid
	if ($action != "upgrade-vms" && $action != "install-vms" && $action != "uninstall-vms") {
		$_GET["error"] = "invalid-action";
		redirect_and_die("settings.farm.php", $_GET);
	}

	foreach ($arch_list as $arch) {
		if ($arch != "amd64" && $arch != "i386" && $arch != "arm64") {
			$_GET["error"] = "invalid-arch";
			redirect_and_die("settings.farm.php", $_GET);
		}

		if (vm_is_installing($arch)) {
			$_GET["error"] = "invalid-action";
			redirect_and_die("settings.farm.php", $_GET);
		}

		if (($action == "install-vms" && vm_is_configured($arch))
			|| ($action == "uninstall-vms" && !vm_is_configured($arch))
			|| ($action == "upgrade-vms" && !vm_is_configured($arch))) {
			$_GET["error"] = "invalid-action";
			redirect_and_die("settings.farm.php", $_GET);
		}
	}

	if (isset($_GET["confirm"])) {
		switch ($action) {
			case "upgrade-vms":
				vm_upgrade($arch_list);
				break;
			case "install-vms":
				vm_install($arch_list);
				break;
			case "uninstall-vms":
				vm_uninstall($arch_list);
				break;
		}
	}
}

$amd64_description	= ($amd64_configured	? "(Installed)" : "(Not Installed)") . ($amd64_installing	? " [Installing]" : "") . ($amd64_upgrading	? " [Upgrading]" : "");
$i386_description	= ($i386_configured		? "(Installed)" : "(Not Installed)") . ($i386_installing	? " [Installing]" : "") . ($i386_upgrading	? " [Upgrading]" : "");
$arm64_description	= ($arm64_configured	? "(Installed)" : "(Not Installed)") . ($arm64_installing	? " [Installing]" : "") . ($arm64_upgrading	? " [Upgrading]" : "");

$amd64_checkbox_class	= ($amd64_configured	? "installed" : "not-installed") . ($amd64_installing	? " installing" : "");
$i386_checkbox_class	= ($i386_configured		? "installed" : "not-installed") . ($i386_installing	? " installing" : "");
$arm64_checkbox_class	= ($arm64_configured	? "installed" : "not-installed") . ($arm64_installing	? " installing" : "");

display_header();
display_error_message();
display_note();
?>

	<main>
		<?php
			if (isset($_GET["submitted"]) && !isset( $_GET["confirm"])) {
				$action_noun = "Action";
				$action_verb = "act";
				$extra_info = "";
				
				switch ($_GET["action"]) {
					case "upgrade-vms":
						$action_noun = "Upgrade";
						$action_verb = "upgrade";
						$extra_info = "<p>This action may take a long time.</p>";
						break;
					case "install-vms":
						$action_noun = "Installation";
						$action_verb = "install";
						$extra_info = "<p>This action may take a long time.</p>";
						break;
					case "uninstall-vms":
						$action_noun = "Uninstallation";
						$action_verb = "uninstall";
						$extra_info = "<p>It may take a long time to re-install the virtual machines.</p>";
						break;
				}

				echo "<div class=\"overlay-container\">
			<div class=\"overlay modal\">
				<div class=\"modal-content\">
					<h2>Confirm $action_noun</h2>
					<p>Are you sure you want to $action_verb the selected virtual machines?</p>
					<p>Selected: ";
					for ($i = 0; $i < count($_GET["arch"]) - 1; $i++) {
						echo $_GET["arch"][$i].", ";
					}
					echo $_GET["arch"][count($_GET["arch"]) - 1];
					echo "</p>
					$extra_info
					<form action=\"settings.farm.php\" method=\"get\">
						<input type=\"hidden\" name=\"submitted\" value=\"true\">
						<input type=\"hidden\" name=\"action\" value=\"".$_GET["action"]."\">
						<input type=\"hidden\" name=\"confirm\" value=\"true\">";
						foreach ($_GET["arch"] as $vm) {
							echo "<input type=\"hidden\" name=\"arch[]\" value=\"$vm\">";
						}
						echo "<button type=\"submit\">Yes</button>
						<a class=\"button\" href=\"settings.farm.php\">No</a>
					</form>
				</div>
			</div>
		</div>".PHP_EOL;
			}
		?>
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
						<h2>Build Farm</h2>
						<h3>Architectures</h3>
						<form action="settings.farm.php" method="get">
							<input type="hidden" name="submitted" value="true">
							<div class="multi-button-form">
								<input type="checkbox" name="arch[]" id="amd64" value="amd64" class="<?php echo $amd64_checkbox_class; ?>">
								<label for="amd64">amd64 <?php echo $amd64_description; ?></label>
								<br>
								<input type="checkbox" name="arch[]" id="i386" value="i386" class="<?php echo $i386_checkbox_class; ?>">
								<label for="i386">i386 <?php echo $i386_description; ?></label>
								<br>
								<input type="checkbox" name="arch[]" id="arm64" value="arm64" class="<?php echo $arm64_checkbox_class; ?>">
								<label for="arm64">arm64 <?php echo $arm64_description; ?></label>
								<br><br>
							
								<button type="submit" name="action" value="upgrade-vms" class="button-vm-upgrade">Upgrade Selected</button>
								&nbsp;
								<button type="submit" name="action" value="install-vms" class="button-vm-install">Install Selected</button>
								&nbsp;
								<button type="submit" name="action" value="uninstall-vms" class="button-vm-uninstall">Uninstall Selected</button>
							</div>
						</form>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>