<?php
/**
 * autobuild-web
 * Copyright (C) 2024 rail5
 * This is free software (GPL 3), and you are permitted to redistribute it under certain conditions
 * Please see the LICENSE file for more information
 */

require_once "global.php";

if (isset($_POST["submitted"])) {
	// Form submitted, create a new user account
	$username = $_POST["username"];
	$password = $_POST["password"];
	$password_confirm = $_POST["password-confirm"];

	if ($password != $password_confirm) {
		$params["error"] = "password-mismatch";
		redirect_and_die("setup.php", $params);
	}

	if ($username == "" || $password == "") {
		$params["error"] = "form-incomplete";
		redirect_and_die("setup.php", $params);
	}

	unset($password_confirm); // Maybe unnecessary

	$password = password_hash($password, PASSWORD_DEFAULT);

	if (!create_user($username, $password)) {
		$params["error"] = "db-error";
		redirect_and_die("setup.php", $params);
	}

	redirect_and_die("index.php");
}

display_header();
display_error_message();
?>

	<main>
		<div class="container">
			<div class="content-wrapper">
				<section class="main-content">
					<div class="card" id="dashboard" style="margin-left: 25%; width: 50%;">
						<h2>Set Up</h2>
						<p>How would you like to sign in?</p>
						<br>
						<form action="setup.php" method="post">
							<input type="text" name="username" placeholder="Enter a new username">
							<input type="password" name="password" placeholder="Enter a new password">
							<input type="password" name="password-confirm" placeholder="Confirm your new password">
							<input type="hidden" name="submitted" value="true">
							<div align="center">
								<button type="submit">Create account</button>
							</div>
						</form>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>