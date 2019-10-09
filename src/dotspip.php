#!/usr/bin/php
<?php

	define('_DOTSPIP_VERSION', '2.0');
	define('_DEBUG', false);
	error_reporting(0);

?><html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Conversion SPIP</title>
<style>
<?php readfile('res/style.css'); ?>
</style>
<script type='text/javascript'>
<?php readfile('res/script.js'); ?>
</script>
</head>
<body>
<div id='top'>
dotSPIP <?php echo _DOTSPIP_VERSION; ?>
</div>
<div id='main'>
<?php

	# a la premiere ouverture, ou a chaque mise a jour,
	# lancer la page de doc
	$prefs = $_SERVER['HOME'].'/Library/Preferences/net.rezo.dotspip.txt';
	$docurl = "http://fil.rezo.net/dotspip.php?version="._DOTSPIP_VERSION;
	if (!@file_exists($prefs)) {
#		`open "$docurl"`;
#		@touch($prefs);
	}


	chdir('src/');

	if (_DEBUG) {
		$out = $_ENV['HOME'].'/Desktop/dotspip/';
		@mkdir($out);
		define('_DIR_OUT', $out);
	}

	require_once 'main.php';
	require_once 'filtres.php';
	require_once 'fonctionsale.php';
	require_once 'html2markdown.php';

	$files = array_slice($argv,1);

	// si pas de drop, gerer le presse-papiers
	/*
	if (!$files)
		$files = array(file_pbpaste());
	*/
	if (!$files)
		echo "<div style='text-align:center'><img src='res/dotspip.png' width='210' height='210' /></div>";
	else
		echo "<div class='minicon'><img src='res/dotspip-small.png' /></div>";


	convert_do($files);

	if ($copied) {
		$mots = str_word_count(join($copied, ' '));
		$s = ($mots > 1 ? 's' : '' );
		echo "<div class='pbcopy'>".$mots." mot$s copi&#233;$s dans le presse-papier</div>\n";
	}


## note il est possible de referencer une image et de l'afficher
# $a = glob(sys_get_temp_dir().'/*.png');
# echo "<img src='".$a[0]."'>";

?>
</div>
<div id='footer'></div>
</body>
</html>
