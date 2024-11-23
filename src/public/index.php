<?php

require_once '../config/config.php';

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Sonic - Coming Soon</title>



		<link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_LINK; ?>assets/favicon/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITE_LINK; ?>assets/favicon/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="<?php echo SITE_LINK; ?>assets/favicon/favicon-16x16.png">
		<link rel="manifest" href="<?php echo SITE_LINK; ?>assets/favicon/site.webmanifest">
		<link rel="mask-icon" href="<?php echo SITE_LINK; ?>assets/favicon/safari-pinned-tab.svg" color="#5bbad5">
		<meta name="msapplication-TileColor" content="#000000">
		<meta name="theme-color" content="#000000">



		<!-- Stylesheets -->
		<link rel="stylesheet" href="<?php echo SITE_LINK; ?>assets/css/core.min.css?v=<?php echo ASSET_VERSION; ?>">
		<link rel="stylesheet" href="<?php echo SITE_LINK; ?>assets/css/style.css?v=<?php echo ASSET_VERSION; ?>">
	</head>
	<body>
		<div class="coming-soon-container">
			<div class="content">
				<div class="logo">SONIC</div>
				<h1>Coming Soon</h1>
				<p class="lead">Powered by a bloke named Claude...</p>
			</div>
		</div>

		<svg class="audio-wave" viewBox="0 0 1000 200" preserveAspectRatio="none">
			<path class="audio-wave-path" d="M0,100 C250,50 750,150 1000,100 L1000,200 L0,200 Z" />
			<path class="audio-wave-path" d="M0,100 C250,150 750,50 1000,100 L1000,200 L0,200 Z" />
		</svg>

		<!-- Scripts -->
		<script src="<?php echo SITE_LINK; ?>assets/js/core.min.js?v=<?php echo ASSET_VERSION; ?>"></script>
		<script src="<?php echo SITE_LINK; ?>assets/js/script.js?v=<?php echo ASSET_VERSION; ?>"></script>
	</body>
</html>