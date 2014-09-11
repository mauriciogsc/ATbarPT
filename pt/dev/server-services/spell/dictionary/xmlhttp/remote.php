<?php
header('Content-type: text/html; charset=UTF-8');

$dictURI = "http://en.wiktionary.org/w/api.php?";

$vars = $_GET;

if(isset($_GET['l'])) {
	if(strtoupper($_GET['l']) == "GB") $_GET['l'] = "en";
	$dictURI = "http://" . strtolower($_GET['l']) . ".wiktionary.org/w/api.php?";
}

$uri = $dictURI . "action=query&titles=" . trim($vars['titles']) . "&rvlimit=1&prop=revisions&rvprop=content&format=json";

$opts = array(
  'http'=>array(
	'method'=>"GET",
	'header'=>"User-Agent: ATBar"
  )
);

$context = stream_context_create($opts);

$remData = file_get_contents( $uri, false, $context );

$rawData = json_decode($remData, true);

require_once("inc/WP/wikiParser.class.php");
require_once("inc/wiky.inc.php");

// If there is data
if(!isset($rawData['query']['pages']['-1'])){
	$wiky = new wiky;
	$parser = new wikiParser();
	foreach($rawData['query']['pages'] as &$page){
		
		// Remove pronounciation
		//var_dump($page['revisions'][0]["*"]);
		$page['revisions'][0]["*"] = preg_replace("/===Pronunciation===.*?===/is", "", $page['revisions'][0]["*"]);
		
		// Remove translations node
		$page['revisions'][0]["*"] = preg_replace("/===Translations===.*?===/is", "", $page['revisions'][0]["*"]);
		
		//$page['revisions'][0]["*"] = $wiky->parse( htmlspecialchars($page['revisions'][0]["*"]) );
		$page['revisions'][0]["*"] = $parser->parse( htmlspecialchars($page['revisions'][0]["*"]) );
		
		// Strip out any curly brackets
		$page['revisions'][0]["*"] = preg_replace("/{{.*?}}/s", "", $page['revisions'][0]["*"]);
		
		// Remove extra li's
		$page['revisions'][0]["*"] = preg_replace("/<li>\s+<\/li>/", "", $page['revisions'][0]["*"]);
		
		// Remove trailing data
		$page['revisions'][0]["*"] = preg_replace("/<h3>Statistics<\/h3>.*/s", "", $page['revisions'][0]["*"]);
		
		
		//echo $wiky->parse( htmlspecialchars($page['revisions'][0]["*"]) );
	}
}

if($vars['v'] == "2" && isset($vars['callback'])){
	echo $vars['callback'] . "(" . trim(json_encode($rawData), "\"") . ");";

} else {
	$ro['data'] = $remData;
	echo "var CSresponseObject = " . json_encode($ro) . ";";
}