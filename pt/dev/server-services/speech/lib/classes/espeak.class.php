<?php

class espeak {

	private $service_url = "espeak";
	
	// Do not change this site address
	// Required for Insipio TTS request
	private $site = "access-vm3.ecs.soton.ac.uk";
	private $request_url;

	private $outputFile = "";
	private $rawText = "";
	private $language;

	public function __construct($text, $outputFile, $language, $voice = null){
		$this->outputFile = $outputFile;
		$this->rawText = $text;
		$this->language = $language;
		$this->voice = $voice;
		if($this->language == "gb") $this->language = "en";
		
		$voiceName = null;
		
		if(!is_null($this->voice)){
			switch($this->language){
				case "en":
					$voiceName = ($this->voice == "male") ? "peter" : "lucy";
					break;
				case "ar":
					$voiceName = ($this->voice == "male") ? "mehdi" : "leila";
					break;
				case "pt":
					$voiceName = ($this->voice == "male") ? null : null;
					break;					
			}
			
			$voiceText = "&voice=".$voiceName;
		}
		else $voiceText = "";
		
		//$this->request_url = $this->service_url . $this->language . "/" . $this->site . "/?pwd=H9pepheswa" . $voiceText . "&text=" . urlencode($this->rawText);
		$this->request_url = $this->service_url . " -v " . $this->language . " -f " . $text . " -w " . $outputFile;
		//echo $this->request_url."\n";
	}

	public function send(){
		return $this->download($this->request_url);

	}

	private function download($path){
		return shell_exec($path);
	}

}