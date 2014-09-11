<?php
header('Content-type: application/json; charset=UTF-8');

require("classes/stemmer.class.php");
require_once("../../lib/db.class.php");
$database = db::singleton("localhost", "dictionary-ar", "ejIJY285JIfhrjkt039IJH983tndo", "services-dictionary-ar");
$database->set_charset("utf8");
$stemmer = new stemmer();

$vars = $_GET;
$lightStem = (isset($vars['ls'])) ? $vars['ls'] : false;

if(!empty($vars['r'])){
	
	$root = $vars['r'];
	$root = $stemmer->normalize($root);
	
	$result = $database->single("SELECT * FROM dictionaryTraditional 
								WHERE root = '".$root."' OR REPLACE(REPLACE(REPLACE(dictionaryTraditional.root, 'آ','ا'), 'إ' ,'ا'), 'أ' ,'ا')	 = '".$root."'");
}
else if(!empty($vars['w'])){
	
	$word = $vars['w'];
	
	$result = $database->single("SELECT * FROM dictionaryWestern WHERE word = '".$word."'");
	
	if(empty($result) && $lightStem){
		$queryWord;
		$wordDa = $stemmer->removeDefiniteArticle($word);
		
		if(empty($result)){
			$word = $stemmer->normalize($word, true);
			
			$result = $database->single("SELECT * FROM dictionaryWestern WHERE normalised COLLATE utf8_unicode_ci = '".$word."' COLLATE utf8_unicode_ci");
			
			if(strlen($wordDa) > 6){
				$result = $database->single("SELECT * FROM dictionaryWestern WHERE normalised like '%".$wordDa."%'");
			}
			else{
				$result = $database->single("SELECT * FROM dictionaryWestern WHERE normalised COLLATE utf8_unicode_ci = '".$wordDa."' COLLATE utf8_unicode_ci");
			}
			
			if(empty($result)){
				if($wordDa != $word){
					$wordSps = $stemmer->removeSubjectPronounSuffix($wordDa);
					$queryWord = $wordSps;
				}
				else{
					$wordOps = $stemmer->removeObjectPronounSuffix($word);
					$wordSps = $stemmer->removeSubjectPronounSuffix($wordOps);
					$wordGp = $stemmer->removeGeneralPrefix($wordSps);
					$wordVp = $stemmer->removeVerbPrefix($wordGp);
					$queryWord = $wordVp;
				}
				$result = $database->single("SELECT * FROM dictionaryWestern WHERE normalised COLLATE utf8_unicode_ci = '".$queryWord."' COLLATE utf8_unicode_ci");
				
				if(empty($result) && strlen($queryWord) > 4){
					$result = $database->single("SELECT * FROM dictionaryWestern WHERE normalised COLLATE utf8_unicode_ci like '%".$queryWord."%' COLLATE utf8_unicode_ci");
				}
			}
		}
	}
}

$callback = (isset($vars['callback'])) ? $vars['callback'] : "";
if(!empty($result)){
	echo $callback . '(' . json_encode($result) . ');';
}
else{
	echo $callback.'({"results":"none"});';
}