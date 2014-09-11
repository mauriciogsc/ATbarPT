<?php

$reqID 	= $_GET['id'];
$data 	= $_GET['data'];
$output = $_GET['o'];

if(@$reqID && @$data && @$output || @$reqID && @$output == 'y'){

	file_put_contents( "../cache/chunks/" . $reqID . ".txt", $data, FILE_APPEND);
	
	if($output == "y"){
		$cleanData = file_get_contents("../cache/chunks/" . $reqID . ".txt");
		unlink("../cache/chunks/" . $reqID . ".txt");
		echo $cleanData;
	} else {
		echo "DataWritten";
	}
}