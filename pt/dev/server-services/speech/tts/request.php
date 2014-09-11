<?php
header('Content-type: application/json; charset=UTF-8');

$vars = $_GET;

list($chunkTotal, $currentChunk) = explode('-', $_GET['chunkData']);

unset($vars['rt'], $vars['chunkData'], $vars['page']);

file_put_contents( "../cache/chunks/" . $vars['id'] . ".txt", $vars['data'], FILE_APPEND);

if($currentChunk == $chunkTotal){
	require("../lib/classes/speech.class.php");
		
	$speech = new speech( file_get_contents("../cache/chunks/" . $vars['id'] . ".txt"), $_GET['page']);
	$speech->setFestivalMode();
	
	if($vars['v'] == "2" && isset($vars['callback'])){
		// This one
		echo $vars['callback'] . "(" . $speech->execute()->returnStatus() . ");";
	} else {
		$ro['data'] = $remData;
		echo "var CSresponseObject = " . $speech->execute()->returnStatus() . ";";
	}
	
	// Logging
	$loadAvg = sys_getloadavg();
	require_once("../../lib/db.class.php");
	$database = db::singleton("localhost", "atbar-stats", "n8sdaw4tjI8wef93dmd", "stats");
	$database->single("INSERT INTO stats.log_tts (host, ip, req_size, cpu_load) VALUES('" . $database->real_escape_string( gethostbyaddr($_SERVER['REMOTE_ADDR']) ) . "', '" . $database->real_escape_string($_SERVER['REMOTE_ADDR']) . "', '" . filesize("../cache/chunks/" . $vars['id'] . ".txt") . "', '" . $database->real_escape_string($loadAvg[0]) . "')");

	
} else {
	$ro['data'] = array('message' => "ChunkSaved", "debugID" => $currentChunk . "-" . $chunkTotal);
	
	if($vars['v'] == "2" && isset($vars['callback'])){
		echo $vars['callback'] . "(" . json_encode($ro) . ");";
	} else {
		$ro['data'] = $remData;
		echo "var CSresponseObject = " . json_encode($ro) . ";";	
	}
		
}

