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
						<h2>Repositories</h2>
						<p>This page is not yet set up. For now, configure your repositories by running <b>'sudo autobuild -s'</b></p>
					</div>
				</section>
			</div>
		</div>
	</main>
</body>
</html>