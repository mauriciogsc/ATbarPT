<?php

class pspell {

	private $pspell;
	private $pspell_config;
	private $pspell_link;
	private $language_path = "/var/www/production/server-services/spell/data";

	private $language;
	private $rawInput;
	private $corrections = array();

	public function __construct($language, $input){
		$this->language = $language;
		$this->rawInput = $input;
		$this->pspell = pspell_new($this->language, "", "", "utf-8");

		// Set up config
		//$this->loadReplacements();
	}

	private function loadReplacements(){
		$path = $this->language_path . $this->language . ".repl";
		if(file_exists($path)){
			//echo file_get_contents($path);
			$this->pspell_config = pspell_config_create($this->language);
			pspell_config_repl($this->pspell_config, $path);
			$this->pspell = pspell_new_config($this->pspell_config);
		}
	}

	public function getCorrections(){

		$wordMatcher = "/\w+/u";
		preg_match_all($wordMatcher, $this->rawInput, $words);

		if(count($words[0]) == 0) return array();

		foreach($words[0] as $k => $word){

			if (!pspell_check($this->pspell, $word)) {
				$suggestions = pspell_suggest($this->pspell, $word);
				$this->corrections[$word]['offset'] = $k;
				$this->corrections[$word]['suggestions'] = $suggestions;
			}
		}
		return $this->corrections;
	}
}

?>
