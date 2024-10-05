<?php
require_once "global.php";

if (isset($_POST["submitted"])) {
	// Form submitted
	if (!isset($_POST["packages"])
		|| !isset($_POST["github-owner"])
		|| !isset($_POST["github-email"])
		|| !isset($_POST["forgejo-url"])
		|| !isset($_POST["forgejo-owner"])) {
			$error_params = array("error" => "form-incomplete");
			redirect_and_die("settings.php", $error_params);
	}

	$old_config = parse_config();

	$new_config = array();
	$new_config["packages"] = array();
	$new_config["github"] = array();
	$new_config["forgejo"] = array();

	$package_list = array_filter(explode(PHP_EOL, $_POST["packages"]));
	for ($i = 0; $i < count($package_list); $i++) {
		$package_list[$i] = trim($package_list[$i]);
		$package_list[$i] = filter_var($package_list[$i], FILTER_SANITIZE_URL);
		$package_list[$i] = filter_var($package_list[$i], FILTER_SANITIZE_ADD_SLASHES);
	}

	$new_config["packages"] = $package_list;
	unset($package_list);

	$new_config["github"]["repo_owner"] = filter_var($_POST["github-owner"], FILTER_SANITIZE_ADD_SLASHES);
	$new_config["github"]["email"] = filter_var($_POST["github-email"], FILTER_SANITIZE_EMAIL);

	$new_config["forgejo"]["instance_url"] = filter_var(filter_var($_POST["forgejo-url"], FILTER_SANITIZE_URL), FILTER_SANITIZE_ADD_SLASHES);
	$new_config["forgejo"]["repo_owner"] = filter_var($_POST["forgejo-owner"], FILTER_SANITIZE_ADD_SLASHES);

	if ($_POST["github-token"] != "") {
		$new_config["github"]["access_token"] = filter_var($_POST["github-token"], FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$new_config["github"]["access_token"] = $old_config["github"]["access_token"];
	}

	if ($_POST["forgejo-token"] != "") {
		$new_config["forgejo"]["access_token"] = filter_var($_POST["forgejo-token"], FILTER_SANITIZE_ADD_SLASHES);
	} else {
		$new_config["forgejo"]["access_token"] = $old_config["forgejo"]["access_token"];
	}

	update_config($new_config);

}

$config = parse_config();

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
				<form action="settings.php" method="post">
					<div class="card" id="packages">
						<h2>Packages</h2>
						Enter <b>Git URLs</b> to your source packages below (<b>one per line</b>)<br>
							<textarea rows="10" style="width: 100%;" name="packages"><?php
							foreach ($config["packages"]["package_urls"] as $url) {
								echo "$url".PHP_EOL;
							}
						?></textarea>
					</div>

					<div class="card" id="github">
						<h2>GitHub</h2>
						<li><label for="github-owner">GitHub Username: </label><input type="text" name="github-owner" id="github-owner" value="<?php echo $config["github"]["repo_owner"]; ?>"></li>
						<br>
						<li><label for="github-email">GitHub Email: </label><input type="text" name="github-email" id="github-email" value="<?php echo $config["github"]["email"]; ?>"></li>
						<br>
						<li><label for="github-token">GitHub Access Token: </label><input type="password" name="github-token" id="github-token" placeholder="Leave blank for no change"></li>
					</div>

					<div class="card" id="forgejo">
						<h2>Forgejo</h2>
						<li><label for="forgejo-url">Forgejo Instance URL: </label><input type="text" name="forgejo-url" id="forgejo-url" value="<?php echo $config["forgejo"]["instance_url"]; ?>"></li>
						<br>
						<li><label for="forgejo-owner">Forgejo Username: </label><input type="text" name="forgejo-owner" id="forgejo-owner" value="<?php echo $config["forgejo"]["repo_owner"]; ?>"></li>
						<br>
						<li><label for="forgejo-token">Forgejo Access Token: </label><input type="password" name="forgejo-token" id="forgejo-token" placeholder="Leave blank for no change"></li>
					</div>

					<div class="card" id="submit">
						<h2>Save Changes</h2>
						<input type="hidden" name="submitted" value="true">
						<button type="submit">Save Changes</button>
						</div>
					</form>
				</section>
			</div>
		</div>
	</main>
</body>
</html>