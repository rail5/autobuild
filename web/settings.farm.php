<?php
require_once "global.php";

/* Build Farm Settings */

$amd64_configured = vm_is_configured("amd64");
$i386_configured = vm_is_configured("i386");
$arm64_configured = vm_is_configured("arm64");

$amd64_description = $amd64_configured ? "Installed" : "Not Installed";
$i386_description = $i386_configured ? "Installed" : "Not Installed";
$arm64_description = $arm64_configured ? "Installed" : "Not Installed";

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
						<h2>Build Farm</h2>
						<h3>Architectures</h3>
						<form action="settings.farm.php" method="get">
							<input type="hidden" name="submitted" value="true">
							<li>
								<input type="checkbox" name="arch[]" id="amd64" value="amd64">
								<label for="amd64">amd64 (<?php echo $amd64_description; ?>)</label>
								<br>
								<input type="checkbox" name="arch[]" id="i386" value="i386">
								<label for="i386">i386 (<?php echo $i386_description; ?>)</label>
								<br>
								<input type="checkbox" name="arch[]" id="arm64" value="arm64">
								<label for="arm64">arm64 (<?php echo $arm64_description; ?>)</label>
							</li>
							<br><br>
							<div class="two-by-two">
								<button type="submit" name="action" value="upgrade-vms">Upgrade Selected</button>
								&nbsp;
								<button type="submit" name="action" value="install-vms">Install Selected</button>
								&nbsp;
								<button type="submit" name="action" value="remove-vms">Remove Selected</button>
							</div>
						</form>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>