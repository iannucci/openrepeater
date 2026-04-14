<html>
	<title>OpenRepeater - Control Panel</title>
	<link href="theme/css/OpenRepeaterSplash.css" rel="stylesheet">
	<link rel="shortcut icon" href="../theme/img/favicon.ico">
</html>

<body id="splash">

	<div class="splash_logo"></div>

	<div class="splash_content">

		<a href="dashboard.php" class="btn" title="Administer OpenRepeater">LOGIN</a>

		<p class="footer_text">Powered by <a href="http://www.svxlink.org/" target="_blank">SvxLink</a>. For more information on this project visit <a href="https://github.com/iannucci/openrepeater" target="_blank">iannucci/openrepeater</a><?php require_once($_SERVER['DOCUMENT_ROOT'].'/includes/orp_git_sha.php'); echo orp_git_sha(); ?></p>

	</div>
</body>
