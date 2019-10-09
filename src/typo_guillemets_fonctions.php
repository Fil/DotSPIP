<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function typo_guillemets_insert_head_css($flux) {
	$flux .= '<link rel="stylesheet" type="text/css" href="'.find_in_path('css/typo_guillemets.css').'" media="all" />'."\n";
	return $flux;
}

/*
Fichier de formatage typographique des guillemets, par Vincent Ramos
<spip_dev AD kailaasa PVNCTVM net>, sous licence GNU/GPL.

Ne sont touchees que les paires de guillemets.

Le formatage des guillemets est tire de
<http://en.wikipedia.org/wiki/Quotation_mark%2C_non-English_usage>
Certains des usages indiques ne correspondent pas a ceux que la
barre d'insertion de caracteres speciaux de SPIP propose.

Les variables suivies du commentaire LRTEUIN sont confirmees par le
_Lexique des regles typographiques en usage a l'Imprimerie nationale_.

Les variables entierement commentees sont celles pour lesquelles
aucune information n'a ete trouvee. Par defaut, les guillements sont alors
de la forme &ldquo;mot&rdquo;, sauf si la barre d'insertion de SPIP proposait
deja une autre forme.
*/
function typo_guillemets_remplacements($texte, $lang=null) {

	// si le texte ne contient pas de guill droit
	// ou s'il contient deja des guillemets élaborés
	// on ne touche pas
	if ((strpos($texte, '"') === false)
	OR (strpos($texte, '«') !== false)
	OR (strpos($texte, '»') !== false)
	OR (strpos($texte, '“') !== false)
	OR (strpos($texte, '”') !== false)
	)
		return $texte;

	if (is_null($lang))
		$lang = $GLOBALS['spip_lang'];

	switch ($lang) {
		case 'fr':
		case 'cpf':
			$guilles="« $2 »"; //LRTEUIN
			break;
		case 'bg':
		case 'cs':
		case 'de':
		case 'hu':
		case 'nl':
		case 'pl':
		case 'ro':
			$guilles="„$2“";
			break;
		case 'ca':
		case 'eo':
		case 'es':
		case 'it':
		case 'it_fem':
		case 'pt':
		case 'pt_br':
		case 'ru':
		case 'tr':
			$guilles="«$2»";
			break;
		case 'da':
			$guilles="»$2«";
			break;
		case 'en':
			$guilles="“$2”"; //LRTEUIN
			break;
		case 'ja':
		case 'zh':
			$guilles="「$2」";
		default:
			$guilles="“$2”";
	}

	// on echappe les " dans les tags ;
	// attention ici \01 est le caractere chr(1), et \$0 represente le tag
	$texte = preg_replace(',<[^>]*"[^>]*(>|$),msSe', "str_replace(\"\'\", \"'\", str_replace('\"','\01', \"\$0\"))", $texte);
	$texte = preg_replace(',<[^>]*&quot;[^>]*(>|$),msSe', "str_replace(\"\'\", \"'\", str_replace('&quot;','\02', \"\$0\"))", $texte);

	// on corrige les guill restants, qui sont par definition hors des tags
	// Un guill n'est pas pris s'il suit un caractere autre que espace, ou
	// s'il est suivi par un caractere de mot (lettre, chiffre)
	$texte = str_replace('&quot;', '"', $texte);
	$texte = preg_replace('/(^|\s)"\s?([^"]*?)\s?"(\W|$)/S', '$1'.$guilles.'$3', $texte);

	// et on remet les guill des tags
	return str_replace("\01", '"', $texte);
	return str_replace("\02", '&quot;', $texte);
}

?>
