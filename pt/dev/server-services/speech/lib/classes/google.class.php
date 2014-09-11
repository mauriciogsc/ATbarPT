<?php

class google {

	private $service_url = "http://translate.google.com/translate_tts";
	private $request_url;
	
	private $outputFile = "";
	private $rawText = "";
	private $language;
	private $mp3;
	private $chunkSize = 75;

	public function __construct($text, $outputFile, $language){
		$this->outputFile = $outputFile;
		$this->rawText = $text;
		$this->language = $language;
		if($this->language == "gb") $this->language = "en";
		
		//$this->request_url = $this->service_url . "?tl=" . $this->language . "&q=" . urlencode($this->rawText);
	}

	public function send(){
		return $this->download();

	}

	private function download(){
		
		$this->request_url = $this->service_url . "?tl=" . $this->language . "&q=";
		
		if(strlen($this->rawText) > $this->chunkSize){
			$textArray = explode(' ', $this->rawText);	
			$i = 0;
			$array[$i] = "";
			
			while(strlen($array[$i]) < $this->chunkSize){
				$array[$i] .= " ".array_shift($textArray);
				
				if(strlen($array[$i]) > $this->chunkSize){
					
					while(strlen($array[$i]) > $this->chunkSize){
						
						$lastSpacePosition = strrpos($array[$i], ' ');
						$lastWordPosition = strlen($array[$i]) - $lastSpacePosition;
						$lastWordPosition = 0 - $lastWordPosition;
						
						$lastWord = substr($array[$i], $lastWordPosition);
						$array[$i] = substr_replace($array[$i] ,"", $lastWordPosition);
						array_unshift($textArray, $lastWord);
						
					}
					$i++;
				}
			}
			$finalArray = $array;
		}
		else{
			$finalArray[0] = $this->rawText;
		}
		
		$data = "";
		
		foreach($finalArray as $a){
			$url = $this->request_url . urlencode($a);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_1) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.24 Safari/535.2");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$data .= curl_exec($ch);
			curl_close ($ch);
		}
		
		if($data){
			file_put_contents($this->outputFile, $data);
			return true;
		}
		else return false;
		
		
	}
}