<?php

function mimeinfo($f) {
	if (!function_exists('finfo_open')) {
		$fesc = escapeshellarg($f);

		# un comm html seule solution pour cacher l'erreur
		echo "<!-- ";
		@exec("file -b --mime-type $fesc", $ret, $err);
		if (!$err)
			$mime = trim($ret[0]);
	} else {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $f);
		finfo_close($finfo);
	}
	return $mime;
}

function accept_mime($mime, $ext) {
	switch ($mime) {
		# .doc, .docx
		case "application/msword":
		case "application/vnd.ms-office":
		case "text/rtf":
		case "text/html":
		case "text/plain":
		case "text/x-php":
		case "text/x-pascal":
		case "application/vnd.oasis.opendocument.text":
			return true;
		case "image/jpeg":
		case "image/gif":
		case "image/png":
			return function_exists('convertimage');
		case 'application/xml':
			return in_array($ext,array('webloc', 'fodt' /* todo */));
		case 'application/octet-stream':
			return in_array($ext,array('webloc'));
		case "application/zip":
			return in_array($ext,array('docx', 'odt', 'epub'));

		# on sait pas
		case "":
			return true;
		default:
			return true;
	}
}

function convert_do($files) {
	global $copied;

	foreach($files as $f) {

		$err = $ret = $html = null;


		# URL
		if (preg_match(',^https?://,', $f)) {
			$html = file_get_contents($f);
			$html = preg_replace(',<(html|head|body),i', '<base href="'.$f.'" />\0', $html);
			($fp = fopen($g = tempnam(dir_tmp(), '.html'), 'w'))
			&& fwrite($fp,$html) && fclose($fp);
			$html = converthtml($g, $err);
			$err = !!!$html;
		}
		# DIR
		else if (is_dir($f)) {
			convert_do(glob("$f/*"));
		}
		# NOT FILE
		else if (!is_file($f)) {
			echo "<h2 class='error'>erreur</h2>";
			echo htmlspecialchars(substr($f, 0,400));
		}
		# UNREADABLE
		else if (!is_readable($f)) {
			echo "<h2 class='error'>".htmlspecialchars(basename($f))." illisible</h2>";
		}
		# FILE
		else {
			$name = basename($f);
			$mime = mimeinfo($f);
			preg_match(',\.([a-z0-9]+)$,i', $f, $r);
			$ext = strtolower($r[1]);

			if (!accept_mime($mime, $ext)) {
				echo "<h2 class='error'>".htmlspecialchars($name)." ($mime)</h2>\n";
			} else {
				if (preg_match(',^image/,', $mime)) {
					convertimage($f, $err);
				}
				# readability ?
				else if ($mime == 'text/html') {
					$html = converthtml($f, $err);
					$err = !!!$html;
				}
				else if ($ext == 'webloc') {
					$loc = file_get_contents($f);
					if (preg_match(',<string>(https?://.*)</string>,Ui', $loc, $r)) {
						convert_do(array($r[1]));
					} else
						$err = 1;
					$err = null;
				}
				else if ($mime == 'text/x-php') {
					$ret = @file_get_contents($f);
					$err = !!!$ret;
					if (!$err)
						$ret = "<code>\n".trim($ret)."\n</code>\n";
				}
				else if ($ext == 'epub') {
					$html = convertepub($f, $err);
					#$err = !strlen(trim($html));
				}
				# doc, docx, etc.
				else {
					$html = converthtml($f, $err);
				}
			}
		}

		if ($err) {
			echo "<h2 class='error'>".htmlspecialchars($name)."</h2>\n";
			echo "<pre>".htmlspecialchars($ret)."</pre>\n";
		}
		else if ($html || $ret) {
			# if (SPIP)
			if ($html) {

				if (_DEBUG) {
					($fp = fopen(_DIR_OUT.basename($f).'.html', 'w'))
					&& fwrite($fp, $html) && fclose($fp);
				}
				$ret = sale($html);
				require_once 'lang_detect.php';
				$a = lang_detect(strip_tags($ret));
				$GLOBALS['spip_lang'] = $a[0];

				$ret = notes_automatiques($ret);

				$ret = nettoyer_utf8($ret);

				$ret = nettoyer_divers($ret);
			}

			# else (MARKDOWN)
			# $html = html2markdown($ret);

			# reperer le titre de la page html sinon le nom de fichier
			if (!(strlen($html)
			AND preg_match(",<title[^>]*>(.*)</title>,Uims", $html, $r)
			AND strlen($titre = html_entity_decode(trim($r[1])))))
				$titre = $name;

			# afficher le resultat
			echo "<h2 class='ok'>".htmlspecialchars($titre)."</h2>\n";
			echo " <textarea>".htmlspecialchars($ret)."</textarea>\n";
			$copied[] = $ret;
			pbcopy(join($copied, "\n\n\n\n-------------------------------------------------------------------\n\n"));
		}

	}
}

function converthtml($f, &$err) {
	$k = escapeshellarg($f);
	exec("textutil -convert html -stdout -noload -nostore $k 2>&1", $ret, $err);
	$ret = join($ret, "\n");
	
	// les notes de bas de page word sont parfois transformees en truc chelou
	$ret = str_replace('<span class="Apple-converted-space"> </span>', '~', $ret);
	
	return $ret;
}

function convertepub($f, &$err) {
	if (function_exists('zip_open')) {
		if (is_resource($zip = zip_open($f))) {
			while ($entry = zip_read($zip)) {
				$l = zip_entry_name($entry);
				if (preg_match(',^.*\.html$,i', $l, $r)) {
					$ret[] = "-------------\n\n{{{".basename($r[0],'.html')."}}}\n\n";
					$ret[] = zip_entry_read($entry, zip_entry_filesize($entry));
				}
			}
		}
	}
	
	# passer par la ligne de commande
	else {
		$k = escapeshellarg($f);
		exec("unzip -l $k 2>&1", $lst, $err);
		$list = array();
		foreach ($lst as $l) {
			if (preg_match(',^.*\.html$,i', $l, $r)) {
				$ret[] = "-------------\n\n{{{".basename($r[0],'.html')."}}}\n\n";
				exec($b = "unzip -p $k ".escapeshellarg($r[0])." 2>&1", $ret, $err);
			}
		}
	}

	return join($ret, "\n");
}

/* 
// exemple : limiter les images a 500px de large, et les deposer sur le bureau
function convertimage($f, &$err) {
	$maxwidth = 400;

	$k = escapeshellarg($f);
	if ($a = getimagesize($f)) {
		if ($a[0] > $maxwidth) {
			$r = $a[0]/$maxwidth;
			$w = $maxwidth;
			$h = ceil($a[1]/$r);

			$dest = _DIR_OUT . $w.'x'.$h.'-'.basename($f);
			$d = escapeshellarg($dest);
# utiliser sips !
# http://www.mactech.com/articles/mactech/Vol.23/23.07/2307MacInTheShell/index.html
# sips -Z 100x100 IMG_1312.JPG --out image1-sized.jpg
#			exec($c = "../res/convert -resize ${w}x${h} $k $d 2>&1", $ret, $err);
		}
	}
}
 */

// envoyer dans le presse-papiers
function pbcopy($text) {
	$d = array(
		0 => array("pipe", "r"),
		1 => array("pipe", "w"),
		2 => array("pipe", "w")
	);

	$cwd = '/tmp';
	$env = array('LANG' => 'en_US.UTF-8');
	$process = proc_open('pbcopy', $d, $pipes, $cwd, $env);

	if (is_resource($process)) {
		fwrite($pipes[0], $text);
		fclose($pipes[0]);
		$ret = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$err = proc_close($process);
	}
}

function dir_tmp() {
	return sys_get_temp_dir().'/';
}

/* recuperer le presse-papiers dans un fichier temporaire */
function file_pbpaste() {
	$ret = pbpaste();
	if ($ret) {
		$ret = bom().$ret;
	}
	($fp = @fopen($f = dir_tmp().'presse-papier-'.getmypid(), 'w'))
	&& fwrite($fp, $ret) && fclose ($fp);

	return $f;
}

function bom() {
	return "\xEF\xBB\xBF";
}

function pbpaste() {
	exec('LANG="en_US.UTF-8" pbpaste -Prefer rtf 2>&1', $ret, $err);
	return join("\n", $ret);
}


/* extrait du _plugins_/revision_nbsp/ */
function notes_automatiques($texte) {

	// Attraper les notes
	$regexp = ', *\[\[(.*?)\]\],msS';
	if (strpos($texte, '[[')
	AND $s = preg_match_all($regexp, $texte, $matches, PREG_SET_ORDER)
	AND $s==1
	AND preg_match(",^ *<>(.*),s", $matches[0][1], $r)) {
		$lesnotes = $r[1];
		$letexte = trim(str_replace($matches[0][0], '', $texte));

		$num = 0;
		while (($a = strpos($lesnotes, '('.(++$num).')')) !== false
		AND (
			($b = strpos($letexte, '('.($num).')')) !== false
			OR ($b = strpos($letexte, '['.($num).'])')) !== false
		)) {
			if (!isset($debut))
				$debut = trim(substr($lesnotes, 0, $a));

			$lanote = substr($lesnotes,$a+strlen('('.$num.')'));

			$lanote = preg_replace(
			',[(]'.($num+1).'[)].*,s', '',$lanote
			);
			$lesnotes = substr($lesnotes, $a+strlen('('.$num.')')+strlen($lanote));
			$lanote = trim($lanote);
			$lanote = (strlen($lanote) ? "[[\n  ".$lanote."\n]]" : '');

			$letexte = substr($letexte,0,$b)
				. $lanote
				. substr($letexte,$b+strlen('('.$num.')'));
		}

		if (strlen($suite = trim($lesnotes)))
			$letexte.= '[[<> '.$suite.' ]]';

		if (isset($debut)) {
			return (strlen($debut)?"\n\n[[<>$debut ]]":'') . $letexte;
		}
	}


	//  Cas deux : on recherche des notes en derniers paragraphes,
	// commencant par (1), on les reinjecte en [[<> ... ]] et on
	// relance la fonction sur cette construction.
	else {
		$texte = trim($texte);
		if (preg_match_all(',^[(](\d+)[)].*$,UmS', $texte, $regs)
		AND preg_match(',^(.*\n)([(]1[)].*)$,UsS', $texte, $u)) {
			$notes = $u[2];
			$texte = $u[1];
			return notes_automatiques("$texte\n\n[[<> $notes ]]");
		} 
	}

	return $texte;
}

function nettoyer_divers($t) {

	// italiques { hoho}, => " {hoho,} "
	$t = preg_replace('@([^}])},@', '\1,}', $t);
	$t = preg_replace('@([^{]){ @', '\1 {', $t);

	// guillemet long de word, trois points
	$t = str_replace('–', '--', $t);
	$t = str_replace('…', '...', $t);

	// - ,
	$t = str_replace('- , ', '--, ', $t);

	// <tbody>
	$t = preg_replace(',\s*</?tbody>\s*,i', "\n", $t);

	// redresser les guillemets
	require_once dirname(__FILE__).'/typo_guillemets_fonctions.php';
	$t = typo_guillemets_remplacements($t);

	// erreurs classiques de typo
	$t = preg_replace('@»[  ]\}@u', '»} ', $t);
	$t = preg_replace('@\}([  ]?»[\.,])@u', '\1}', $t);
	$t = preg_replace('@\}([  ]?»)@u', '\1}', $t);
	$t = preg_replace('@(«[  ]?)\{([^{]|$)@u', '{\1\2', $t);
	$t = preg_replace('@(^|[^{}])(\{ \}|\} \{)([^{]|$)@u', '\1\3', $t);

	// espaces et insecables en fin de ligne
	$t = preg_replace(',[ ~ ]+$,um', "", $t);

	// multiples sauts de ligne
	$t = preg_replace(',\n\n\n+,m', "\n\n", $t);

	return trim($t);
}

function utf8_do($r) {
	return mb_convert_encoding($r[0], 'UTF-8', 'HTML-ENTITIES');
}

function nettoyer_utf8($t) {
	if (!preg_match('!\S!u', $t))
		$t = preg_replace_callback(',&#x([0-9a-f]+);,i', 'utf8_do', utf8_encode(utf8_decode($t)));
	return $t;
}

?>
