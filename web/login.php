<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GNU Affero GPL v3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

if ($_SESSION['logged-in']) {
	redirect_and_die("index.php");
}

if (isset($_POST["submitted"])) {
	// Form submitted, create a new user account
	$username = $_POST["username"];
	$password = $_POST["password"];

	if ($username == "" || $password == "") {
		$params["error"] = "form-incomplete";
		redirect_and_die("login.php", $params);
	}

	if (!log_in($username, $password)) {
		$params["error"] = "invalid-credentials";
		redirect_and_die("login.php", $params);
	}

	$_SESSION['logged-in'] = true;
	redirect_and_die("index.php");
}

display_header();
display_error_message();
?>

	<main>
		<div class="container">
			<div class="content-wrapper">
				<section class="main-content">
					<div class="card" id="login">
						<h2>Log In</h2>
						<form action="login.php" method="post">
							<input type="text" name="username" placeholder="Username">
							<input type="password" name="password" placeholder="Password">
							<input type="hidden" name="submitted" value="true">
							<div align="center">
								<button type="submit">Log In</button>
							</div>
						</form>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>