<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

$signing_keys = get_signing_keys();

if (isset($_GET["action"]) && !isset($_GET["error"])) {
	// Form submitted
	if (is_array($_GET["action"])) {
		redirect_and_die("self");
	}
	switch ($_GET["action"]) {
		case "create":
			// Do we have everything we need?
			if (empty($_GET["key_email"])) {
				redirect_and_die("signing-keys.php", array("error" => "form-incomplete"));
			}

			if (empty($_GET["key_name"])) {
				redirect_and_die("signing-keys.php", array("error" => "form-incomplete"));
			}

			$key_email = $_GET["key_email"];
			$key_name = $_GET["key_name"];

			// Validate input
			if (!filter_var($key_email, FILTER_VALIDATE_EMAIL)) {
				redirect_and_die("signing-keys.php", array("error" => "invalid-email"));
			}

			// Create the key
			create_signing_key($key_name, $key_email);

			redirect_and_die("signing-keys.php");
			break;
		case "delete":
			if (isset($_GET["confirm"])) {
				foreach ($_GET["delete"] as $key) {
					delete_signing_key($key);
				}

				redirect_and_die("signing-keys.php");
			}
			break;
	}
}

display_header();
display_error_message();
?>

	<main>
		<?php
			if (isset($_GET["delete"]) && !isset( $_GET["confirm"])) {
				echo "<div class=\"overlay-container\">
			<div class=\"overlay modal\">
				<div class=\"modal-content\">
					<h2>Confirm Deletion</h2>
					<p>Are you sure you want to delete the selected keys?</p>
					<p>Keys: ";
					for ($i = 0; $i < count($_GET["delete"]) - 1; $i++) {
						echo $_GET["delete"][$i].", ";
					}
					echo $_GET["delete"][count($_GET["delete"]) - 1];
					echo "</p>
					<p>This action is <b>irreversible</b>.</p>
					<form action=\"signing-keys.php\" method=\"get\">
						<input type=\"hidden\" name=\"action\" value=\"delete\">
						<input type=\"hidden\" name=\"confirm\" value=\"true\">";
						foreach ($_GET["delete"] as $key) {
							echo "<input type=\"hidden\" name=\"delete[]\" value=\"$key\">";
						}
						echo "<button type=\"submit\">Yes</button>
						<a class=\"button\" href=\"signing-keys.php\">No</a>
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
					<div class="card" id="dashboard">
						<?php
						if (!empty($signing_keys)) {
							echo "<h2>Signing Keys</h2>
						<form action=\"signing-keys.php\" method=\"get\" style=\"display: inline-block;\">
								<input type=\"hidden\" name=\"action\" value=\"delete\">
								<button type=\"submit\" class=\"no-decoration\">Delete selected keys</button>
								<br>";
								foreach ($signing_keys as $signing_key) {
									$checkbox_disabled = "";
									$in_use_message = "";

									if (signing_key_is_in_use($signing_key["email"])) {
										$checkbox_disabled = " disabled";

										$in_use_message = " &nbsp; <i>(in use by: ";
										
										$repos = get_repos_which_use_signing_key($signing_key["email"]);

										for ($i = 0; $i < count($repos) - 1; $i++) {
											$in_use_message .= $repos[$i].", ";
										}
										$in_use_message .= $repos[count($repos) - 1].")</i>";
									}

									echo "<input type=\"checkbox\" name=\"delete[]\" value=\"".$signing_key["email"]."\" id=\"".$signing_key["email"]."\"$checkbox_disabled> ";
									echo "<label for=\"".$signing_key["email"]."\">".$signing_key["name"]." &lt;".$signing_key["email"]."&gt;</label>$in_use_message<br>".PHP_EOL;
								}
						echo "
						</form>
					</div>";
						}
						?>
					<div class="card" id="dashboard">
						<h2>Create New Signing Key</h2>
						<form action="signing-keys.php" method="get" style="display: inline-block;">
								<input type="hidden" name="action" value="create">
								<h3>Key Details</h3>
								<label for="key_name">Key Name:</label>
								<input type="text" name="key_name" placeholder="my-signing-key"><br>
								
								<label for="key_email">Key Email:</label>
								<input type="text" name="key_email" placeholder="signing-key@email.com">

								<br><br>

								<button type="submit">Create Key</button>
						</form>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>
