<?php
header('Content-type: application/json; charset=UTF-8');
require_once("classes/pspell.class.php");


$language = isset($_GET['l']) ? $_GET['l'] : exit;
$language = ($language == "GB" OR $language == "en") ? "en" : $language;
//$language = ($language == "pt") ? "pt_BR" : $language;

$word = isset($_GET['r']) ? $_GET['r'] : exit;

$speller = new pspell($language, $word);

$corrections = $speller->getCorrections();

if(isset($_GET['callback'])){
	echo $_GET['callback'] . "(";
	echo json_encode($speller->getCorrections());
	echo ");";
}
else{
	echo json_encode($speller->getCorrections());
}

?>
