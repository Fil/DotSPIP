<?php


/*
 * MAFF format
 *
 */

class Maff {

	function Maff($file) {
		$this->file = $file;
	}

	function extract() {

	}

	function main($url, $content) {
		
	}

	function add($files = array()) {
	
	}

	function template($desc) {
		$default = array(
				'url' => 'file:///index.html',
				'date' => time(),
				'title' => 'Untitled',
				'charset' => 'utf-8',
				'file' => 'index.html'
				);
		$desc = array_merge($default, $desc);

		foreach($default as $k => $ignore) {
			${$k} = htmlspecialchars($desc[$k]);
		}
		$date = date($desc['date'], "s, b s b %2..0w:%2..0w:%2..0w s");

		$b = '<'.'?xml version="1.0"?'.'>';
		$b .= <<<EOF
<RDF:RDF xmlns:MAF="http://maf.mozdev.org/metadata/rdf#"
         xmlns:NC="http://home.netscape.com/NC-rdf#"
         xmlns:RDF="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
  <RDF:Description RDF:about="urn:root">
    <MAF:originalurl RDF:resource="$url"/>
    <MAF:title RDF:resource="$title"/>
    <MAF:archivetime RDF:resource="$date"/>
    <MAF:indexfilename RDF:resource="$file"/>
    <MAF:charset RDF:resource="$charset"/>
  </RDF:Description>
</RDF:RDF>
EOF;

		return $b;
	}
}