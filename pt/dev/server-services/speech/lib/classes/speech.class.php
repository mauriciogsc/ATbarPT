<?php
error_reporting(E_ERROR | E_WARNING);
//error_reporting(E_ALL);
/**
 * speech class.
 * 
 */
class speech {

	const METHOD_FESTIVAL = 1;
	const METHOD_INSIPIO = 3;
	const METHOD_GOOGLE = 4;
	const METHOD_ESPEAK = 5;

	private $audio_method;
	private $debug = 0;

	private $rawData;
	private $decodedData;
	private $cleanData;
	private $chunks;
	private $uri;
	private $voice;
	private $fileMappings = array();
	
	private $uniqueID; // This speech allocation's unique ID
	private $loadMultiplier = 1; // Multiplier for CPU load, used to estimate compilation time.
	private $maxLoad = 4; // Maximum CPU load.
	public $encodingState = 0;
	private $averageChunkTime = 2; // Time in TTS countdown
	private $splitBy = 400; // characters to split the string by.
	private $maxOffset = 50; // Maximum offset for splitby for finding the seperating character.
	private $maxPageLength = 30000; // characters
	private $cacheTimeout = 3600; // 1h
	private $scratch_path = "/var/www/production/core/atbar/pt/dev/server-services/speech/cache/";
	private $output_path = "/var/www/production/core/atbar/pt/dev/server-services/speech/cache/";
	private $script_path = "/var/www/production/core/atbar/pt/dev/server-services/speech/";
	private $output_uri = "https://core.atbar.org/atbar/pt/dev/server-services/speech/cache/";

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $rawData
	 * @param mixed $uri
	 * @return void
	 */
	public function __construct($rawData, $uri){
		$this->uri = $uri;
		$this->voice = $voice;
		$this->rawData = $rawData;
	
		$loadAvg = sys_getloadavg();
		
		if($loadAvg[0] > $this->maxLoad){
			// Do not allow encoding, we're over max load.
			$this->encodingState = -1;
		}
		else{
			// Get the ID for this URL.
			$this->generateID();
		}
	}
	
	/**
	 * sanitizeString function.
	 * 
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	function sanitizeString($data){
        //$alterations = array('!' => 'exclem', '#' => 'pound', '?' => 'question mark', ';' => 'semicolon', ':' => 'colon', '[' => 'left bracket', '\\' => 'back slash', ']' => 'right bracket', '^' => 'carat', '_' => 'underscore', '`' => 'reverse apostrophe', '|' => 'pipe', '~'=>'tilde', '"' => 'quote', '$' => 'dollar', '%' => 'percent', '&' => 'ampersand', '\'' => 'apostrophe', '(' => 'open paren', ')' => 'close paren', '*' => 'asterisk', '+' => 'plus', ',' => 'comma', '-' => 'dash', '.' => 'dot', '/' => 'slash', '{' => 'open curly bracket', '}' => 'close curly bracket', '"' => 'quote', 'bigham' => 'biggum', 'cse' => 'C S E', 'url' => 'U R L');
        
        // Use for special words such as ATbar
        $alterations = array(
        	"�" => "oe",
        	"&#156;" => "oe",
        	"am" => "aye em",
        	"ATBar" => "aye tee bar",
        	"plugin" => "pl	ug in",
        	"console" => "con sole"
        );
        
        // Use for special characters like apostrophes
        $characterAlterations = array(
        	"�","'","`",'"'
        );
        
        //Strip tags and content that we dont want.
        $data = $this->strip_tags_content($data, "<script><noscript>", TRUE);
        if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Stripping Tags. Data: $data\n\n", FILE_APPEND);
        
        
        // Put a ttssilence at the end of a heading to force a pause.
        $clean = preg_replace( "/(<h[0-9]>)(.*?)(<\/h[0-9]>)/i", " $2. ", $data );
        if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Altering Headings. Data: $clean\n\n", FILE_APPEND);
        
        // Strip all tags, apart from the ones we want to give context to.
        //$clean = strip_tags( $clean, "<a><img><iframe><input><textarea>" );
        //if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Removed non-required tags. Data: $clean\n\n", FILE_APPEND);
        
        // Convert tags to what we want to be read out.
		//IMG's
		$clean = preg_replace( "/(<img.*?((alt\s*=\s*(?<q>'|\"))(?<text>.*?)\k<q>.*?)?>)/i", ". Image: $5 ", $clean );
		if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Images. Data: $clean\n\n", FILE_APPEND);
		
		
		// Inputs
		$clean = preg_replace( "/(<input.*?type\s*?=\s*?(?:\"|')(.*?)(?:\"|').*?\/>)/i", ". $2 Input ", $clean );
		//This isnt working.
		if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Inputs. Data: $clean\n\n", FILE_APPEND);
		
		
		// Text areas.
		$clean = preg_replace_callback( "/(<textarea.*?>((.*?)<\/textarea>)?)/i", "speech::match_textareas", $clean );		
		if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Text Areas. Data: $clean\n\n", FILE_APPEND);
		
		
		// Links
		/*$clean = preg_replace_callback( "/(<a.*?((href\s*=\s*(?<q>'|\"))(?<text>.*?)\k<q>.*?)?>)/i", "speech::match_links", $clean ); */
		$clean = preg_replace_callback( "/(<a.*?>(.*?)<\/a>)/i", "speech::match_links", $clean );
		if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Links. Data: $clean\n\n", FILE_APPEND);
		
		// Video
		$clean = preg_replace("/<embed.*?>/is", " Flash Video ", $clean);
		
        // Remove comments.
        $clean = preg_replace( "/(<!--.*?-->)/s", "", $clean );
        
        // Add pauses
        //$clean = preg_replace( "/(,|\.)[ |A-Z]/s", " ttssilence ", $clean );
        
        // Apostrophies cause problems.
        //$clean = preg_replace( "/[\w]{2,}('|�)s/s", "", $clean );
		
		// Remove special punctuation
        $clean = str_replace($characterAlterations, '', $clean);
        if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Character Alterations. Data: $clean\n\n", FILE_APPEND);
        
		
		// Remove any orphaned tags.
		$clean = preg_replace( "/(<.*?>)/s", "", $clean );
        if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Orphaned Tags. Data: $clean\n\n", FILE_APPEND);
        
        
		// Remove all duplicate newlines and tabs
        $clean = preg_replace( "/(\r|\n|	)/", "", $clean );   
        if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Newlines. Data: $clean\n\n", FILE_APPEND);     
        
        $clean = str_ireplace( array_keys($alterations), array_values($alterations), $clean );
        if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Alterations. Data: $clean\n\n", FILE_APPEND);
        
        $clean = html_entity_decode( $clean );
        
        
        if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sanitizeString:> Sanitisation complete. Data: $clean\n\n", FILE_APPEND);
        
        $this->decodedData = $clean;
        
        $this->sseek( $clean );
	}

	/**
	 * sseek string seeking function. Split a block of text up into chunks. Try and do it intelligently i.e. find where near spaces are rather than just splitting in the middle of a word.
	 * 
	 * @access private
	 * @param mixed $data
	 * @return void
	 */
	private function sseek($data){		
		$outString = "";
		$start = 0;
		$chunks = ceil(strlen($data) / $this->splitBy);

		if(strlen($data) > $this->splitBy){
			
			for($i=1; $i <= $chunks; $i++){
				$chunk = ltrim( substr( $data, $start, $this->splitBy ), ".");
				$chunk = preg_replace("/ [ \r\n\v\f]+/", " ", $chunk);
				
				// Find the closest space at the end of the string within offset distance.
				// Where is the location of the last dot?
				$loc = strrpos($chunk, ".");
				
				$diff = strlen($chunk) - $loc;
				
				// Is there a space within the offset distance inside this part of the string?
				if($diff < $this->maxOffset && $i != $chunks && $loc !== false){
					// Yes, take up to that point, alter the offset for the next run to a negative so that it does'nt lose any data.
					$chunk = substr($chunk, 0, $loc);
					$start += 0 - $diff;
					
					if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sseek:> Using current chunk, dot found. Offset: $start\n", FILE_APPEND);
				} else {
					// No. We're going to have to take some of the next string up to maxoffset. Go grab it.
					$forwardChunk = substr( $data, $start + $this->splitBy, $this->maxOffset );
					$forwardChunk = preg_replace("/ [ \r\n\v\f]+/", " ", $forwardChunk);
					
					// Is there a dot in the next forwardchunk?
					$fwloc = strpos($forwardChunk, ".");
					
					if($fwloc !== FALSE){
						$chunk .= substr($forwardChunk, 0, $fwloc);
						$start += $fwloc;
						
						if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sseek:> Using fwchunk, dot found. Offset: $start\n", FILE_APPEND);
					} else {
						$spaceLoc = strrpos($chunk, " ");
						
						if($spaceLoc !== FALSE){
							// Use the normal chunk, but find the closest space to the end to split by.
							$spaceDiff = strlen($chunk) - $spaceLoc;
							$chunk = substr($chunk, 0, $spaceLoc);
							$start += 0 - $spaceDiff;
							if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sseek:> Using current chunk, no dot found, using space. Offset: $start\n", FILE_APPEND);
						} else {
							if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sseek:> Using current chunk, no dot found, no space found.\n", FILE_APPEND);
							// Look for a space in the next chunk in the max offset.
						}

						// Plan B, we're going to have to leave it as it is.
					}
				}
				
				$start += $this->splitBy;
				$outString .= $chunk . "\n";
				$this->addMapping($chunk);
			}
		} else {
			if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "sseek:> Data size is under chunk size. Using a single chunk. Data: $data\n\n", FILE_APPEND);
			$outString = $data;
			$this->addMapping($data);
		}
		
		$this->cleanData = $outString;
		// this WILL NOT WORK with large datasets. Use while() through the dataset rather than an if statement for splitBy.
		$this->chunks = $chunks;
	}

	/**
	 * match_links function.
	 * 
	 * @access private
	 * @static
	 * @param mixed $matches
	 * @return void
	 */
	private static function match_links($matches){
		if(count($matches) < 2) return " ";
		
		if($matches[2] != ""){
			//return ". Link to " . $matches[5] . " ";
			return " link " . $matches[2] . " ";
		} else {
			return " ";
		}
	}
	
	private static function match_textareas($matches){
		if(count($matches) > 3){
			return " textarea input " . $matches[3] . " ";
		} else {
			return " textarea Input ";
		}
	}
	
	/**
	 * writeTranscript function.
	 * 
	 * @access private
	 * @return void
	 */
	private function writeTranscript(){
		file_put_contents("{$this->scratch_path}/{$this->uniqueID}.txt", $this->cleanData);
		touch("{$this->scratch_path}/{$this->uniqueID}.txt");
	}

	/**
	 * writePlaylist function.
	 * 
	 * @access private
	 * @return void
	 */
	private function writePlaylist(){
		$playlist = new DOMDocument();
		
		$root = $playlist->createElement('playlist');
		$root_el = $playlist->appendChild($root);
		$root_el->setAttribute("version", "1");
		
		$tl = $playlist->createElement("trackList");
		$tracks = $root_el->appendChild($tl);
		
		for($i=0; $i <= $this->chunks-1; $i++){
		
			$track = $playlist->createElement("track");
			
			$tracks->appendChild($track);
			
			$title = $playlist->createElement("title");
			$title_el = $track->appendChild($title);
			$title_el->appendChild($playlist->createTextNode($i));

			$location = $playlist->createElement("location");
			$location_el = $track->appendChild($location);
			
			$ttsPrefix = "";
			switch($this->audio_method){
				case speech::METHOD_FESTIVAL:
					$ttsPrefix = "TTS-";
					break;
				case speech::METHOD_INSIPIO:
					$ttsPrefix = "INSIPIO-TTS-";
					break;
				case speech::METHOD_GOOGLE:
					$ttsPrefix = "GOOGLE-TTS-";
					break;
				case speech::METHOD_ESPEAK:
					$ttsPrefix = "ESPEAK-TTS-";
					break;
					
			}
			
			$location_el->appendChild($playlist->createTextNode($this->output_uri . $ttsPrefix . $this->uniqueID . "-" . $i . ".mp3"));
			
			$meta = $playlist->createElement("meta");
			$meta_el = $track->appendChild($meta);
			$meta_el->setAttribute("rel", "type");
			$meta_el->appendChild($playlist->createTextNode("sound"));
		
		}
		
        $playlistJSON = str_replace(array("\n", "\r", "\t"), '', $playlist->saveXML());
        $playlistJSON = trim(str_replace('"', "'", $playlistJSON));
        $simpleXml = simplexml_load_string($playlistJSON);
        $json = json_encode($simpleXml);
		
		file_put_contents($this->output_path . $this->uniqueID . ".json", $json);
		touch($this->output_path . $this->uniqueID . ".json");
		
		$playlist->save($this->output_path . $this->uniqueID . ".xml");
		touch($this->output_path . $this->uniqueID . ".xml");
		
	}
	
	/**
	 * generateID function.
	 * 
	 * @access private
	 * @return void
	 */
	private function generateID(){
		$components = parse_url( $this->uri );
		//$this->uniqueID = substr(md5(substr(md5($components['host']), 0, 5) . substr(md5($components['path'] . $components['query']), -5, 5)), 0, 5) . substr(time(), -4, 4);
		
		$id = sha1( sha1(time() . $_SERVER['REMOTE_HOST'] . rand(1, 9999)) . rand(1, 9999));
		while(file_exists($this->output_path . $id . ".xml")) $id = sha1( sha1(time() . $_SERVER['REMOTE_HOST'] . rand(1, 9999)) . rand(1, 9999));
		
		$this->uniqueID = $id;
		
	}
	
	/**
	 * addMapping function.
	 * 
	 * @access private
	 * @param mixed $line
	 * @return void
	 */
	private function addMapping($line){
		$components = explode(" ", $line);
		//print_r($components);
		$this->fileMappings[] = array(	$components[0] . " " . $components[1] . " " . $components[2],
										$components[count($components)-2] . " " . $components[count($components)-1] . " " . $components[count($components)]);
	}
	
	/**
	 * dataIsCached function.
	 * 
	 * @access private
	 * @return void
	 */
	private function dataIsCached() {
		$this->checkCache();
		return false;
	}
	
	/**
	 * checkCache function.
	 * 
	 * @access private
	 * @return void
	 */
	private function checkCache(){
	
	}
	
	/**
	 * flushCache function.
	 * 
	 * @access public
	 * @return void
	 */
	public function flushCache(){

		$cacheDir = dir($this->output_path);
		
		//echo "Handle: " . $cacheDir->handle . "\n";
		//echo "Path: " . $cacheDir->path . "\n";
		while (false !== ($entry = $cacheDir->read())) {
			if($entry !== "." && $entry !== ".."){
				$statData = stat($cacheDir->path);
		   		//echo date('l jS \of F Y h:i:s A', $statData['atime']) . "<br />\n";
		   		// If the file was modified after the max cache time...
		   		if( ($statDate['atime'] + $this->cacheTimeout) < time() ) {
		   			//echo "File is older than cache time. <br />";
		   			//$this->deleteFile($cacheDir->path);
		   		}
		   	}
		}
		$cacheDir->close();
	
	}
	
	private function deleteFile($path){
		if(file_exists($path)){
			//$status = unlink($path);
		}
	}
	
	/**
	 * execute function.
	 * 
	 * @access public
	 * @return this object
	 */
	public function execute(){
		if($this->encodingState > -1){
		
			switch($this->audio_method){
				case speech::METHOD_FESTIVAL:
					$this->decode();
					$this->initSanitise();
					$this->writePlaylist();
					$output = shell_exec("nice -n 19 /var/www/production/server-services/speech/tts/SB_generateAudio.sh {$this->scratch_path}{$this->uniqueID}.txt {$this->uniqueID} awb");
				break;
				
				case speech::METHOD_INSIPIO:
					require_once(dirname(__FILE__) . "/insipio.class.php");
					
					$this->decode();
					$this->initSanitise();
					
					$path = $this->output_path . "INSIPIO-TTS-" . $this->uniqueID . "-" . 0 . ".mp3";

					$language = isset($_GET['l']) ? strtolower($_GET['l']) : "en";
					
					$in = new insipio($this->decodedData, $path, $language, $this->voice);
					
					$response = $in->send();
					
					$this->chunks = 1;
					$this->writePlaylist();
					
					// Flush any out of date fields.
					$this->flushCache();
				break;
				
				case speech::METHOD_GOOGLE:
					require_once(dirname(__FILE__) . "/google.class.php");
					
					$this->initSanitise();
					
					$path = $this->output_path . "GOOGLE-TTS-" . $this->uniqueID . "-" . 0 . ".mp3";

					$language = isset($_GET['l']) ? strtolower($_GET['l']) : "en";
					
					$google = new google($this->rawData, $path, $language);
					
					$response = $google->send();
					
					$this->chunks = 1;
					$this->writePlaylist();
					
					// Flush any out of date fields.
					$this->flushCache();
				break;

				case speech::METHOD_ESPEAK:
					require_once(dirname(__FILE__) . "/espeak.class.php");
					
					$this->decode();
					$this->initSanitise();
					
					$path = $this->output_path . "ESPEAK-TTS-" . $this->uniqueID . "-" . 0 . ".mp3";

					$language = isset($_GET['l']) ? strtolower($_GET['l']) : "en";
					
					$in = new espeak($this->output_path . $this->uniqueID . ".txt", $path, $language, $this->voice);
					
					$response = $in->send();
					
					$this->chunks = 1;
					$this->writePlaylist();
					
					// Flush any out of date fields.
					$this->flushCache();
				break;
			}
			
			// SCS fix 13/09/2011
			// Don't wait for output. Version 2 of the toolbar waits for a response, which will hang the UI unless this is run in the background.
			// Sending to /dev/null & achieves this. Left the old command above just incase we need to put back at a later point.
			//exec("nice -n 19 /var/scripts/SB_generateAudio.sh {$this->scratch_path}{$this->uniqueID}.txt {$this->uniqueID} awb > /dev/null &");
			if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "execute:> Running generateAudio on dataset: {$this->clean}\n\n", FILE_APPEND);
			if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "execute:> result: " . $output . "\n\n", FILE_APPEND);
			//file_put_contents($this->scratch_path . "chunks/clean-" . rand(0, 99) . ".txt", $this->returnClean() );
			
			//echo $output;
			//echo "Execution string: /var/scripts/SB_generateAudio.sh {$this->scratch_path}/{$this->uniqueID}.txt {$this->uniqueID} awb";
		}
		return $this;
	}
	
	/**
	 * returnStatus function.
	 * 
	 * @access public
	 * @return void
	 */
	public function returnStatus(){
		if($this->encodingState > -1 && $this->encodingState < 1){
			return json_encode( array("status" => "encoding", "ID" => $this->uniqueID, "chunks" => $this->chunks, "est_completion" => round(($this->averageChunkTime * $this->chunks) * $this->loadMultiplier), "map" => $this->fileMappings) );
		} else {
			return json_encode( array("status" => "failure", "reason" => "overcapacity") );
		}
	}
	
	/**
	 * returnClean function.
	 * 
	 * @access public
	 * @return void
	 */
	public function returnClean(){
		return $this->cleanData;
	}

	/**
	 * strip_tags_content function.
	 * 
	 * @access private
	 * @param mixed $text
	 * @param string $tags. (default: '')
	 * @param mixed $invert. (default: FALSE)
	 * @return void
	 */
	private function strip_tags_content($text, $tags = '', $invert = FALSE) {
	
		preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
		$tags = array_unique($tags[1]);
		
		if(is_array($tags) && count($tags) > 0) {
			if($invert == FALSE) {
			  return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
			}else {
			  return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
			}
		} elseif($invert == FALSE) {
			return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
		}
		return $text;
	}

	private function decode(){
		// Decode b64 data recieved from the client. Note that JS can't send + or / without causing issues, so we've swapped these for different characters.
		$this->decodedData = base64_decode( str_replace(array("-", "_"), array("/", "+"), $this->rawData) );
		
		//if($this->debug == 1) file_put_contents($this->scratch_path . "chunks/decod-" . rand(0, 99) . ".txt", $decoded);
		
		// Sanitize tags and phoneticise elements for TTS.
		if($this->debug == 1) file_put_contents($this->script_path . "debug/" . $this->uniqueID . ".txt", "__construct:> Decoding complete. Data: {$this->decodedData}\n\n", FILE_APPEND);
	}
	
	private function initSanitise(){
		// Sanitise
		$this->sanitizeString($this->decodedData);
		$this->writeTranscript();
	}

	public function setFestivalMode(){
		$this->audio_method = speech::METHOD_FESTIVAL;
	}
	
	public function setInsipioMode(){
		$this->audio_method = speech::METHOD_INSIPIO;
	}
	
	public function setGoogleMode(){
		$this->audio_method = speech::METHOD_GOOGLE;
	}

	public function setEspeakMode(){
		$this->audio_method = speech::METHOD_ESPEAK;
	}
	
	public function setVoiceType($voice){
		$this->voice = $voice;
	}
}