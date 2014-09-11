<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

if(isset($_GET['l'], $_GET['t']) == FALSE){
	echo json_encode(array("error" => "not enough arguments specified"));
} else {
	$leading = $_GET['l'];
	$trailing = $_GET['t'];

	$dataURL = 'http://lslvm-scs4.ecs.soton.ac.uk/' . $_GET['lang'] . '/predict?l=' . rawurlencode($leading) . '&t=' . rawurlencode($trailing);

	$data = file_get_contents($dataURL);

	$response = json_encode(array("status" => "OK", "payload" => $data));

	if(isset($_GET['callback'])){
		echo $_GET['callback'] . "(" . $response . ");";
	} else {
		echo $response;
	}
}


?>
