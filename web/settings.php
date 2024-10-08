<?php
require_once "global.php";

/* Settings Portal */

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
					<div class="mini-card-label">
						<h1>Settings</h1>
					</div>
					<br>
					<div class="mini-card-container">
						<a class="card mini-card" href="settings.web.php">
							<h3>Web</h3>
							<img src="img/web.svg" alt="autobuild-web">
							Web UI Configuration
						</a>
						<a class="card mini-card" href="settings.autobuild.php">
							<h3>Autobuild</h3>
							<img src="img/config.svg" alt="autobuild">
							Packages, GitHub, Forgejo

						</a>
						<a class="card mini-card" href="settings.farm.php">
							<h3>Build Farm</h3>
							<img src="img/vm.svg" alt="Build Farm">
							VM Configuration
						</a>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>