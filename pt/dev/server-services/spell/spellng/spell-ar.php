<?php
header('Content-type: application/json; charset=UTF-8');
require_once("classes/pspell.class.php");

$language = isset($_GET['l']) ? $_GET['l'] : "ar";
if(!isset($_GET['r'])) exit;

$speller = new pspell($language, $_GET['r']);

$corrections = $speller->getCorrections();

if(isset($_GET['callback'])){
        echo $_GET['callback'] . "(";
        echo json_encode($speller->getCorrections());
        echo ");";
} else {
        echo json_encode($speller->getCorrections());
}

?>
