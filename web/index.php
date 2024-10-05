<?php
require_once "global.php";

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
					<div class="card" id="dashboard">
						<h2>Dashboard</h2>
						<p>Welcome to Autobuild, your automated Debian package builder and distributor.</p>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>