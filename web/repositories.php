<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

$debian_repos = get_debian_repos();

$signing_keys = get_signing_keys();

$github_configured = github_is_configured();

if (isset($_GET["action"]) && !isset($_GET["error"])) {
	// Form submitted
	if (is_array($_GET["action"])) {
		redirect_and_die("self");
	}
	switch ($_GET["action"]) {
		case "create":
			// Do we have everything we need?
			if (empty($_GET["repo_url"])) {
				$_GET["error"] = "no-repo-url";
				redirect_and_die("repositories.php", $_GET);
			}

			if (empty($_GET["repo_name"])) {
				$_GET["error"] = "no-repo-name";
				redirect_and_die("repositories.php", $_GET);
			}

			$repo_name = $_GET["repo_name"];
			$repo_url = $_GET["repo_url"];
			$use_existing_key = isset($_GET["use_existing_key"]);
			$signing_key = $use_existing_key ? $_GET["signing_key"] : $_GET["key_email"];
			$github_pages = isset($_GET["github_pages"]);
			$github_pages_url = $github_pages ? $_GET["github_pages_url"] : "";

			// Validate input
			if (preg_match("/[^a-zA-Z0-9\-\_]/", $repo_name)) {
				$_GET["error"] = "invalid-repo-name";
				redirect_and_die("repositories.php", $_GET);
			}

			if (!filter_var($repo_url, FILTER_VALIDATE_URL)
			&& !filter_var($repo_url, FILTER_VALIDATE_DOMAIN)
			&& !filter_var($repo_url, FILTER_VALIDATE_IP)) {
				$_GET["error"] = "invalid-repo-url";
				redirect_and_die("repositories.php", $_GET);
			}

			if (!filter_var($github_pages_url, FILTER_VALIDATE_URL)
			&& $github_pages_url != "") {
				$_GET["error"] = "invalid-github-pages-url";
				redirect_and_die("repositories.php", $_GET);
			}

			// Generate a new signing key (if we have to)
			if (!$use_existing_key) {
				create_signing_key($_GET["key_name"], $_GET["key_email"]);
			}

			// Create the repository
			create_debian_repo($repo_name, $repo_url, $signing_key, $github_pages, $github_pages_url);

			redirect_and_die("repositories.php");
			break;
		case "delete":
			if (empty($_GET["delete"])) {
				$_GET["error"] = "invalid-repo";
				redirect_and_die("repositories.php", $_GET);
			}

			foreach($_GET["delete"] as $repo) {
				if (!in_array($repo, $debian_repos)) {
					redirect_and_die("repositories.php", array("error" => "invalid-repo"));
				}
			}

			if (isset($_GET["confirm"])) {
				delete_debian_repos($_GET["delete"]);
				redirect_and_die("repositories.php");
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
					<p>Are you sure you want to delete the selected repositories?</p>
					<p>Repositories: ";
					for ($i = 0; $i < count($_GET["delete"]) - 1; $i++) {
						echo $_GET["delete"][$i].", ";
					}
					echo $_GET["delete"][count($_GET["delete"]) - 1];
					echo "</p>
					<p>This action is <b>irreversible</b>.</p>
					<form action=\"repositories.php\" method=\"get\">
						<input type=\"hidden\" name=\"action\" value=\"delete\">
						<input type=\"hidden\" name=\"confirm\" value=\"true\">";
						foreach ($_GET["delete"] as $repo) {
							echo "<input type=\"hidden\" name=\"delete[]\" value=\"$repo\">";
						}
						echo "<button type=\"submit\">Yes</button>
						<a class=\"button\" href=\"repositories.php\">No</a>
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
						if (!empty($debian_repos)) {
							echo "<h2>Repositories</h2>
						<form action=\"repositories.php\" method=\"get\" style=\"display: inline-block;\">
								<input type=\"hidden\" name=\"action\" value=\"delete\">
								<button type=\"submit\" class=\"no-decoration\">Delete selected repositories</button>
								<br>";
								foreach ($debian_repos as $debian_repo) {
									echo "<input type=\"checkbox\" name=\"delete[]\" value=\"$debian_repo\" id=\"$debian_repo\"> <label for=\"$debian_repo\">$debian_repo</label><br>".PHP_EOL;
								}
						echo "
						</form>
					</div>";
						}
						?>
					<div class="card" id="dashboard">
						<h2>Create New Repository</h2>
						<form action="repositories.php" method="get" style="display: inline-block;">
								<input type="hidden" name="action" value="create">
								<h3>Repository Details</h3>
								<label for="repo_name">Repository Name:</label>
								<input type="text" name="repo_name" placeholder="my-debian-repo"><br>
								
								<label for="repo_url">Repository URL:</label>
								<input type="text" name="repo_url" placeholder="https://my.site/deb">

								<br><br>

								<h3>Signing Key</h3>

								<?php
									if (!empty($signing_keys)) {
										echo '<input type="checkbox" name="use_existing_key" id="use_existing_key" class="unhide" checked>
										<label for="use_existing_key"> Use Existing Key</label>
										<div class="hidden">
											<select name="signing_key">'.PHP_EOL;
											foreach ($signing_keys as $key) {
												echo "<option value=\"".$key["email"]."\">".$key["name"]." &lt;".$key["email"]."&gt;</option>".PHP_EOL;
											}
											echo "</select>
										</div>";
									}
								?>

								<div class="shown">
										<label for="key_name">New Signing Key Name:</label>
										<input type="text" name="key_name" placeholder="Example Name">
										<br>
										<label for="key_email">New Signing Key Email:</label>
										<input type="text" name="key_email" placeholder="example@email.com">
								</div>

								<br>

								<h3>Extra</h3>

								<input type="checkbox" name="github_pages" id="github_pages" class="unhide2"<?php if (!$github_configured) echo " title=\"Your GitHub credentials are not configured\" disabled"; ?>>
								<label for="github_pages"<?php if (!$github_configured) echo " title=\"Your GitHub credentials are not configured\""; ?>>This repository will be served via GitHub Pages</label>

								<div class="hidden2">
									<label for="github_pages_url">GitHub Pages URL:</label>
									<input type="text" name="github_pages_url" placeholder="https://github.com/user/repository.git">
								</div>

								<br><br>

								<button type="submit">Create Repository</button>
						</form>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>