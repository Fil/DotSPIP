<?php

	/*
		La Fonction Sale()
		(c)2005 James <klike@free.fr>
		d'aprËs le bouton memo et le script spip_unparse
	*/
	
	/*
		Attention: version beaucoup plus violente: 
		il s'agit ici de recuperer du HTML qui n'est de toute facon pas genere par SPIP
		mais par OpenOffice
	*/
	
	function _tag2attributs($innerTag){
	  $pattern1 = "([a-zA-Z_]*)\s*=\s*[']([^']*)[']";
	  $pattern2 = '([a-zA-Z_]*)\s*=\s*["]([^"]*)["]';
	  preg_match_all (",$pattern1,Uims", $innerTag, $attr1, PREG_SET_ORDER);
	  preg_match_all (",$pattern2,Uims", $innerTag, $attr2, PREG_SET_ORDER);
	  $attributs = array();
	  foreach ($attr1 as $key => $value) {
	  	$attributs[$value[1]]=$value[2];
	  }
	  foreach ($attr2 as $key => $value) {
	  	$attributs[$value[1]]=$value[2];
	  }
	  ksort($attributs);
	  return $attributs;
	}
	
	function _decode_entites($texte){
		static $trans;
		if (!isset($trans)) {
			$trans = get_html_translation_table(HTML_ENTITIES, $quote_style);
			$trans = array_flip($trans);
#			$trans["&euro;"]='€';
#			$trans["&oelig;"]='œ';
#			$trans["&OElig;"]='Œ';
			foreach ($trans as $key => $value){
			   $trans['&#'.ord($value).';'] = $value;
			}
			// ajout du caractere apostrophe SPIP : í
//			$trans['&#8217;'] = "'";
//			$trans['&#039;'] = "'";
//			$trans['&#171;'] = "«";
//			$trans['&#187;'] = "»";
//			$trans['&#176;'] = "°";
			// des caracteres non supportes
#			$trans["&nbsp;&euro;"]=' €';

			# si &amp; est mentionne specifiquement, conserver un caractere
			# de facon a pouvoir ecrire "é s'écrit <html>&eacute;</html>"
			$trans['&amp;'] = '&#38;';


	  	if ($GLOBALS['meta']['charset'] == 'utf-8'){
				foreach ($trans as $key=>$value)
					$transutf[$key]=utf8_encode($value);
				$trans = $transutf;
			}
		}
		return strtr($texte, $trans);
	}

	function _correspondances_standards() {
		return array(
			//Mise en page
			",<(i|em)( [^>\r]*)?".">,Uims" => "{", //Italique
			",</(i|em)>,Uims" => "}", //Italique
			",<(b|strong)( [^>]*)?".">,Uims" => "{{", //Gras
			",</(b|strong)>,Uims" => "}}", //Gras
			",<(b|h[4-6]|strong)( [^>]*)?".">(.+)</\\1>,Uims" => "{{\\3}}", //Gras
			",<(h[1-6])( [^>]*)?".">([[:blank:]]?)</\\1>,Uims" => "", //Intertitre
			",<(h[1-3])( [^>]*)?".">(.+)</\\1>,Uims" => "\r{{{ \\3 }}}\r", //Intertitre

			//Liens, ancres & notes
			",<a[\s][^<>]*href=\s*['\"]([^<>'\"]*)['\"][^<>]*>\s*?(.*)\s*<\/a>,Uims" => "[\\2->\\1]", //Lien externe

			//Paragraphes
//			",<(p)( [^>]*)?"."></\\1>,Uims" => "", //Faux Paragr. (on s'en fiche)
			",<(p)( [^>]*)?".">(.+)</\\1>,Uims" => "\r\r\\3\r\r", //Paragr. (ajouter \r avant aussi)
			",(<no p[^>]*>)(\s*)(<\/no>),Uims" => "", // spiperie
			",(<\/no p>)(.*)(<no p[^>]*>),Uims" => "\\2", // spiperie
			",\s*?<w?br(\/)?( [^>]*)?".">\r-(&nbsp;|\s),Ui" => "\r- ", 
			",\s*?<w?br(\/)?( [^>]*)?".">\r?,Ui" => "\r_ ", //Saut de ligne style suivi par du texte
//			",(\s*(<br( [^>]*)?".">)?)*\\z,i" => "", //Saut de ligne en fin de texte (ca deconne severe, texte qui disparait completement)
			",<hr( [^>]*)?".">,Uims" => "\r----\r", //Saut de page
			
			",<(pre)( [^>]*)?".">(.+)</\\1>,Uims" => "<poesie>\n\\3\n</poesie>", //Poesie

			",<(blockquote)( [^>]*)?".">(\s*?),Uims" => "<quote>\n", //quote
			",(\s*?)</blockquote>,Uims" => "\n</quote>", //quote

			",<form [^>]*><div><textarea [^>]*spip_cadre[^>]*>(.*)</textarea></div></form>,Uims" => "<cadre>\\1</cadre>",

			//typo
			",&nbsp;:,i" => " :", 
			",\r-(&nbsp;|\s),i" => "\r- ", 
			",\r_[[:blank:]](\r[[:blank:]]?)+," => "\r\r", // BR suivis d'autres sauts de ligne deviennent paragraphes
			",(\r[[:blank:]]?)+\r_[[:blank:]]," => "\r\r",
			",\$[[:blank:]]+," => "\r",
			//Images & Documents
		);
	}

	function _correspondances_a_bas_le_html() {
	
		return array(
		
		// on ne veut pas des heads / html / body 
		",<doctype[^>]*>,ims" => "",
		",<head>.*<\/head>,Uims" => "",
		",<html>,Uims" => "",
		",<\/html>,Uims" => "",
		",<body.*>,Uims" => "",
		",<\/body>,Uims" => "",
	
		// on ne veux pas des tables
		",<table.*>,Uims" => "",
		",<\/table.*>,Uims" => "",
		",<tr.*>,Uims" => "",
		",<\/tr>,Uims" => "",
		",<td.*>,Uims" => "",
		",<\/td>,Uims" => "",
	
	
		// on ne veux pas des div
		",<div.*>,Uims" => "",
		",<\/div.*>,Uims" => "",
		
		// divers et variÈs 
		",<csobj.*>,Uims" => "",
		",<\/csobj>,Uims" => "",
		",<csscriptdict.*>,Uims" => "",
		",<\/csscriptdict>,Uims" => "",
		",<spacer.*>,Uims" => "",
		
		// javascript sur les liens 
		",target=\".*\",Uims" => "",
		",onmouseover=\".*\",Uims" => "",
		",onmouseout=\".*\",Uims" => "",
		",onclick=\".*\",Uims" => "",
		
	
		// c est pas du html mais je le met ici quand meme
		",\t,Uims" => " ",
		
		// ramasse-miettes: trucs qui restent
		",<(font|large|small|iframe|a|span|script|p|dl|dd|dt|sup|sdfield|u|form|fieldset|button|input|textarea|select|option|label|noscript|link|abbr|acronym|address)( [^>]*)?>,Uims" => "",
		",<\/(font|large|small|iframe|a|span|script|p|dl|dd|dt|sup|sdfield|u|form|fieldset|button|input|textarea|select|option|label|noscript|link|abbr|acronym|address)( [^>]*)?>,Uims" => "",
		",<center.*>,Uims" => "\n\n",
		",<\/center.*>,Uims" => "\n\n",
		",<col.*>,Uims" => "\n\n",
		",<\/col.*>,Uims" => "\n\n",
		
	
			);
	}
	
	// les listes numerotees ou non ---------------------------------------------------------------
	function _extraire_listes($texte){
	  $pattern = "(<ul[^>]*>|</ul>|<ol[^>]*>|</ol>)";
	
	  preg_match_all (",$pattern,Uims", $texte, $tagMatches, PREG_SET_ORDER);
	  $textMatches = preg_split (",$pattern,Uims", $texte);
	
	  $niveau = 0;
	  $prefixe= "-**********";
	  $prefixenum= "-##########";
	  $niveaunum = 0;
	  $texte = $textMatches [0];
	  
	  if (count($textMatches)>1){
		  for ($i = 1; $i < count ($textMatches)-1; $i ++) {
		  	if (preg_match(",<ul[^>]*>,is",$tagMatches [$i-1][0])) {$niveau++;$pref = $prefixe;}
		  	else if (preg_match(",<ol[^>]*>,is",$tagMatches [$i-1][0])) {$niveau++;$pref = $prefixenum;}
		  	else if (strtolower($tagMatches [$i-1][0])=="</ul>") $niveau--;
		  	else if (strtolower($tagMatches [$i-1][0])=="</ol>") $niveau--;
		  	$pre = "";
		  	if ($niveau) $pre = substr($pref,0,$niveau+1);
		  	if (strlen(trim($textMatches [$i]))){
			  	$lignes = preg_split(",<li[^>]*>,i",$textMatches [$i]);
			  	foreach ($lignes as $key=>$item){
			  		$lignes[$key] = trim(str_replace("</li>","",$item));
			  		if (strlen($lignes[$key]))
			  			$lignes[$key]="$pre " . $lignes[$key];
			  		else 
			  			unset($lignes[$key]);
			  	}
					$texte .= implode("\r",$lignes)."\r";
		  	}
		  }
		  $texte .= end($textMatches);
	  }
	  return $texte;
	}
	
	// les tableaux standards ou personalises -----------------------------------------------------
	function _recompose_tableau($innerTag,$texte){
		$table_class=array('spip'=>"|",'ville'=>"£");
		$sep = $table_class['spip'];

		$attributs = _tag2attributs($innerTag);
// detecter la classe
		$class = "";
		if (isset($attributs['class']))
			$class = $attributs['class'];
		if (isset($table_class[$class]))
			$sep = $table_class[$class];

		// d'abord transformer tous les | en leur entite pour pas se tromper
		$texte = str_replace($sep,"&#".ord($sep).";",$texte);

		$summary = "";
		$caption = "";
		if (isset($attributs['summary']))
			$summary = trim($attributs['summary']);
		if (preg_match(",<caption>(.*)</caption>,Uims",$texte))
			$caption = trim(preg_replace(",.*?<caption>(.*)</caption>.*?,Uims","\\1",$texte));
		if (strlen($caption) || strlen($summary))
			$summary="$sep$sep $caption $sep $summary $sep$sep\r";
		
		// les lignes
		$texte = preg_replace(",<tr[^>]*>\s*?(.*)\s*</tr>,Uims","\r$sep \\1$sep\r",$texte);
		
		// les colonnes
		$texte = preg_replace(",<(td)(\s[^>]*)?".">[[:blank:]]*(.*)\s*?</\\1>,Uims"," \\3 $sep",$texte);
		$texte = preg_replace(",<(th)(\s[^>]*)?".">[[:blank:]]*({{)?(.*?)(}})?\s*?</\\1>,ims"," {{\\4}} $sep",$texte);
		// attention un |\n correspond a une fin de ligne et ne doit pas etre autorise au milieu d'une ligne
		$texte = preg_replace(",(\s\\$sep)\r,Uims","\\1 \r",$texte);
		// les doubles pipes en fin de ligne doivent etre remis en simpes |
		$texte = str_replace("$sep$sep\r","$sep\r",$texte);
		
		// le thead
		// verifier d'abors qu'il y a un vrai contenu dans le thead
		$texte = preg_replace(",\r(\\$sep\s*{{[\s\r_]*}}\s*)*\\$sep\r,Uims","",$texte);
		$texte = preg_replace(",<thead[^>]*>\s*?(.*)\s*</thead>,Uims","\\1\r",$texte);
		// le tbody
		$texte = preg_replace(",<tbody[^>]*>\s*?(.*)\s*</tbody>,Uims","\\1",$texte);
		// le table
		//$texte = preg_replace(",<table[^>]*>\s*?(.*)\s*</table>,Uims","\\1\r\r",$texte);
		
		// caption/head
		$texte = preg_replace(",<caption>.*</caption>\s*?,Uims","\\1",$texte);
		$texte = $summary . $texte;

		// les lignes vides inter |
		$texte = preg_replace(",\\$sep\r[\s\r]*\\$sep,Uims","$sep\r$sep",$texte);
		
		return $texte;
	}	
	
	function _extraire_tableaux($texte){
		// tableaux
	  $pattern = '<table([^>]*)>(.*)</table>';
	  preg_match_all (",$pattern,Uims", $texte, $tableMatches, PREG_SET_ORDER);
	  $textMatches = preg_split (",$pattern,Uims", $texte);
	
	  foreach ($tableMatches as $key => $value) {
			$tableMatches [$key][0] = _recompose_tableau ($value[1],$value[2]);
	  }
		for ($i = 0; $i < count ($textMatches); $i ++) {
			$textMatches [$i] = $textMatches [$i] . $tableMatches [$i] [0];
		}
		$texte = implode ("", $textMatches);
		return $texte;
	}
	
	// les balises img vers les <imgxx> (ou <docxx> ou <embxx>) -----------------------------------------------
	function _retrouve_document($type,$fulltag,$innerTag,$shorttag){
		static $puce=false;
		static $puce_attr=false;
		if (!$puce){
			if (function_exists('definir_puce')) $puce = definir_puce();
			$puce_attr = _tag2attributs($puce);
		}
		$attributs = _tag2attributs($innerTag);
		// est-ce la puce ?
		$test = array_diff_assoc($puce_attr,$attributs);
		if (count($test)==0 && count($puce_attr)==count($attributs))
			return "\r-";
		// sinon recherche dans spip_documents
		$src = "";
		if ($type=='img' && isset($attributs['src'])) // balise img
			$src = $attributs['src'];
		else if ($type=='object' && isset($attributs['data'])) // balise object
			$src = $attributs['data'];

		if (function_exists('spip_query'))
		if (strlen($src)){
			// si le chemin fait reference a IMG, le ramener a la racine
			if (substr($src,0,strlen(_DIR_IMG))==_DIR_IMG)
				$src = str_replace(_DIR_RACINE,"",$src);
			$src = _q($src); // soyons prudent tout de meme ...
			$query = "SELECT id_document FROM spip_documents WHERE fichier='$src'";
			$res = spip_query($query);
			if ($row=spip_fetch_array($res)){
				$id_document = $row['id_document'];
				$align="";
				if (isset($attributs['class'])){
		  		preg_match_all (",spip_documents_([a-zA-Z_]*),ims", $attributs['class'], $classMatches, PREG_SET_ORDER);
		  		foreach($classMatches as $value)
		  			$align.="|".$value[1];
				}
				$fulltag = "<$shorttag$id_document$align>";
				return $fulltag;
			}
		}
		
		return $fulltag;
	}
	function _extraire_images_spip($texte){
		static $liste_tests=array(
			',<(img)\s([^<>]*)>,Uims'=>'img', // image
			',<(object)\s([^<>]*)>.*</object>,Uims'=>'emb' // object
			);
		foreach($liste_tests as $pattern=>$shortcut){
		  preg_match_all ($pattern, $texte, $tagMatches, PREG_SET_ORDER);
		  $textMatches = preg_split ($pattern, $texte);
	
		  foreach ($tagMatches as $key => $value) {
				$tagMatches [$key][0] = _retrouve_document ($tagMatches[$key][1],$tagMatches[$key][0],$tagMatches[$key][2],$shortcut);
		  }
			for ($i = 0; $i < count ($textMatches); $i ++) {
				$textMatches [$i] = $textMatches [$i] . $tagMatches [$i] [0];
			}
			$texte = implode ("", $textMatches);
		}
		// reconnaitre les |center sur img
		$texte = preg_replace(",<div\s[^>]*class\s*=\s*['\"][^'\"]*spip_documents_([a-zA-Z_]*?)[^'\"]*['\"][^>]*>\s*<(img)([0-9]*)(|[^>]*)?>\s*</div>,Uims","<\\2\\3\\4|\\1>",$texte);
		// reconnaitre les |center sur doc qui ont ete detectees comme des img
		$texte = preg_replace(",<div\s[^>]*class\s*=\s*['\"][^'\"]*spip_documents_([a-zA-Z_]*?)[^'\"]*['\"][^>]*>\s*<(img)([0-9]*)(|[^>]*)?>\s*<div[^>]*class='spip_doc_titre'[^>]*>.*</div>\s*<div[^>]*class='spip_doc_descriptif'[^>]*>.*</div>\s*</div>,Uims","<doc\\3\\4|\\1>",$texte);
		// reconnaitre les |center sur emb
		$texte = preg_replace(",<div\s[^>]*class\s*=\s*['\"][^'\"]*spip_documents_([a-zA-Z_]*?)[^'\"]*['\"][^>]*>\s*<(emb)([0-9]*)(|[^>]*)?>\s*<div[^>]*class='spip_doc_titre'[^>]*>.*</div>\s*<div[^>]*class='spip_doc_descriptif'[^>]*>.*</div>\s*</div>,Uims","<\\2\\3\\4|\\1>",$texte);

		// object
		
		
		return $texte;
	}
	

	// extraire code
	function _extraire_code($contenu){
		$pattern =",<(div|span) [^>]*spip_code[^>]*><code>(.*)</code></\\1>,Uims";
		preg_match_all ($pattern, $contenu, $codeMatches, PREG_SET_ORDER);
		$textMatches = preg_split ($pattern, $contenu);

		foreach ($codeMatches as $key => $value) {
			$codeMatches [$key][0] = "<code>" . preg_replace(",<br[^>]*>\s*,i","\r",$value[2]) . "</code>";
		}
		for ($i = 0; $i < count ($textMatches); $i ++) {
			$textMatches [$i] = $textMatches [$i] . $codeMatches [$i] [0];
		}
		$contenu = implode ("", $textMatches);
		return $contenu;
	}
	// extraire poesie
	function _extraire_poesie($contenu){
		$pattern =",<div [^>]*spip_poesie[^>]*>((\s*<div>.*</div>)*\s*)</div>,Uims";
		preg_match_all ($pattern, $contenu, $poesieMatches, PREG_SET_ORDER);
		$textMatches = preg_split ($pattern, $contenu);

		foreach ($poesieMatches as $key => $value) {
			$poesieMatches [$key][0] = "<poesie>" . preg_replace(",\s*<div>(.*)</div>,Uim","\r\\1",$value[1]) . "</poesie>";
		}
		for ($i = 0; $i < count ($textMatches); $i ++) {
			$textMatches [$i] = $textMatches [$i] . $poesieMatches [$i] [0];
		}
		$contenu = implode ("", $textMatches);
		return $contenu;
	}

	function _spip_avant_sale($contenu) {
		if(function_exists('avant_sale'))
			return avant_sale($contenu);

		$contenu = preg_replace(',<!doctype[^>]*>\s*,si', '', $contenu);

		// PRETRAITEMENTS
		$contenu = str_replace("\n\r", "\r", $contenu); // echapper au greedyness de preg_replace
		$contenu = str_replace("\r\n", "\r", $contenu); // dojo produit du \r\n
		$contenu = str_replace("\n", "\r", $contenu);
		$contenu = str_replace("&mdash;", "--", $contenu);
	
		// virer les commentaires html (qui cachent souvent css et jajascript)
		$contenu = preg_replace("/<!--.*-->/Uims", "", $contenu);

		$contenu = preg_replace("/<(script|style)\b.+?<\/\\1>/i", "", $contenu);
		$contenu = preg_replace(",[\r\n],", " ", $contenu); // Virer les retours chariot pour rendre le texte lisible - les paragaphes et br les reintroduiront la ou necessaire

		# en cas de presence de nombreux insecables, les nettoyer
		if (substr_count($contenu, '&nbsp;') > sqrt(strlen($contenu)/10)) {
			$contenu = preg_replace(',\s*&nbsp;\s*,', ' ', $contenu);
		}
		return $contenu;
	}

	function _sucrer_cesures ($contenu) {
		$contenu = preg_replace("/(&#8203;|&#173;|­|​​)/", "", $contenu); // Cesures discretes
		$contenu = str_replace("’", "'", $contenu); // Corriger les apostrophes de Word
		$contenu = str_replace("&rsquo;", "'", $contenu); // Corriger les apostrophes de Word
		$contenu = str_replace("&lsquo;", "‘", $contenu); // 
		return $contenu;
	}

	function _spip_apres_sale($contenu) {
		if(function_exists('apres_sale'))
			return apres_sale($contenu);

		// POST TRAITEMENT
		$contenu = str_replace("\r", "\n", $contenu);
		$contenu = preg_replace(",\n_ --,", "\n--", $contenu);
		$contenu = preg_replace(",\n_ }}},", "}}}\n", $contenu); // Corriger les fin de groupes apres le retour chariot
		$contenu = preg_replace(",\n_ }},", "}}\n", $contenu);
		$contenu = preg_replace(",\n_ },", "}\n", $contenu);
		$contenu = preg_replace(",(\n_[[:blank:]]?)?\n-,", "\n-", $contenu);
		$contenu = preg_replace(",[[:blank:]]+,", " ", $contenu); // Regrouper les espaces multiples
		$contenu = preg_replace(",{{{[[:blank:]]?}}},", "", $contenu); // Sucrer les h3 vides
		$contenu = preg_replace(",{{[[:blank:]]?}},", "", $contenu); // Sucrer les bolds vides
		$contenu = preg_replace(",{[[:blank:]]?},", "", $contenu); // Sucrer les italiques vides
		$contenu = preg_replace(",}}{{,", "", $contenu); // Regrouper les bold successifs
		$contenu = preg_replace(",([^}])}{([^{]),", "$1$2", $contenu); // Regroupes les italiques successifs
		$contenu = preg_replace(",\n[[:blank:]]+,", "\n", $contenu); // Supprimer les espaces en debut de ligne
		$contenu = preg_replace(",\n_[[:blank:]](\n[[:blank:]]?)+," , "\n\n", $contenu); // BR suivis d'autres sauts de ligne deviennent paragraphes
		$contenu = preg_replace(",(\n[[:blank:]]?)+\n_[[:blank:]]," , "\n\n", $contenu);

		$contenu = preg_replace(",\n(?=\n\n),","",$contenu);
		
		// Virer les caracteres speciaux (notamment cesures discretes)
		$contenu = _sucrer_cesures($contenu);

		// virer les entites a la fin seulement
		// &nbsp; est utilise pour reperer des trucs genre "- " en debut de ligne ...
		$contenu = _decode_entites($contenu);
		
		return $contenu;
	}

	function _images_base64($c) {
		preg_match_all('@(<img\b.*src=(["\']))data:(.*);base64,@UimsS', $c, $rs, PREG_SET_ORDER);
		foreach ($rs as $r) {
			$a = strpos($c, $r[0]);
			$l = strlen($r[0]);
			$b = strpos($c, $r[2], $a+strlen($r[0]));
			$image = base64_decode(substr($c,$a+$l,$b-$a-$l));
			// enregistrer l'image # todo: type mime
			$img = md5($image).'.bin';
			fwrite($fw = fopen(_DIR_TMP.'upload_office/'.$img, 'w'), $image); fclose($fw);
			$c = substr_replace($c, $img, $a+strlen($r[1]), $b-$a-strlen($r[1]));
		}
		return $c;
	}

	function sale($contenu_sale, $correspondances = '') {
		$contenu_propre = $contenu_sale;

		$contenu_propre = _images_base64($contenu_propre);

		//PrÈ  Traitement
		$contenu_propre = _spip_avant_sale($contenu_propre);
		

		//Traitement
		if(empty($correspondances))
			$correspondances = _correspondances_standards();
			

		$contenu_propre = _extraire_images_spip($contenu_propre);



		$contenu_propre = _extraire_images($contenu_propre);

		
		foreach($correspondances as $motif => $remplacement) {
			$contenu_propre = preg_replace($motif, $remplacement, $contenu_propre);
		}
		
		$contenu_propre = _extraire_listes($contenu_propre);

		$contenu_propre = _extraire_tableaux($contenu_propre);
		$contenu_propre = _extraire_code($contenu_propre);
		$contenu_propre = _extraire_poesie($contenu_propre);
		//reconnaitre les url d'articles, rubriques ...
		// DESACTIVE (on traite des fichiers Office qui n'ont donc pas de liens SPIP)
		//		$url_look = url_de_base()."spip.php";
		//		$contenu_propre = preg_replace(",\[(.*)->\s*$url_look"."[^\]]*id_((art)icle|(rub)rique)=([0-9]*?)[^\]]*],Uims","[\\1->\\4\\5]",$contenu_propre);

		// a priori on garde ce qui est pas analysÈ
		foreach(_correspondances_a_bas_le_html() as $motif => $remplacement)
			$contenu_propre = preg_replace($motif, $remplacement, $contenu_propre);

		//Post Traitement
		$contenu_propre = _spip_apres_sale($contenu_propre);

		return trim($contenu_propre);
	}


function _extraire_images($texte) {
	$texte = preg_replace(
	",\[(<img [^>]*src=[\"\']([^\"\']+)[\"\'][^>]*>)\-\>([^\]]*\.(jpg|gif|png))\],i", '<!-- image \2 -->',
	$texte);
	$texte = preg_replace(",(<img [^>]*src=[\"\']([^\"\']+)[\"\'][^>]*>),i", '<!-- image \2 -->', $texte);

###### on peut laisser comme ça ou trouver un modele
###### <importer|image=truc>
###### (avec une méthode qui va bien via dropbox ou upload ?)
###### deux cas : images distantes, images locales.

###### le modele peut importer direct si le fichier est accessible http
###### et proposer un form d'upload sinon (dans le privé), et rien (dans le public)

	return $texte;
}


?>