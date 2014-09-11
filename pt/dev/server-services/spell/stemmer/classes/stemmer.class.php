<?php
class Stemmer {
	
	//Normalization removes diacritics and replaces frequently misspelled letters with a unified representation
	public function normalize($word, $isWord = false) {
		$result = str_replace("ٌ","",$word);
		$result = str_replace("ْ","",$result);
		$result = str_replace("ّ","",$result);
		$result = str_replace("ٍ","",$result);
		$result = str_replace("ِ","",$result);
		$result = str_replace("ُ","",$result);
		$result = str_replace("ً","",$result);
		$result = str_replace("َ","",$result);
		
		if($isWord) $result = $this->normalizeWord($result);
		return $result;
	}
	
	//Normalization removes diacritics and replaces frequently misspelled letters with a unified representation
	private function normalizeWord($word) {
		$result = str_replace("أ","ا",$word);
		$result = str_replace("إ","ا",$result);
		$result = str_replace("آ","ا",$result);
		return $result;
	}
	//Remove the difinit Article from the beginnig of the word--------------------------------------------------
	public function removeDefiniteArticle($word) {
		if(strlen($word)>6)
		{
			$shortArticles = Array("ال","لل");
			$temp = substr($word, 0, 4);
			if(in_array($temp,$shortArticles))
			{
				return substr($word, 4, strlen($word)-4);
			}
		}
		if(strlen($word)>8)
		{
			$mediumArticles = Array("بال","كال","وال","فال","ولل","فلل");
			$temp = substr($word, 0, 6);
			if(in_array($temp,$mediumArticles))
			{
				return substr($word, 6, strlen($word)-6);
			}
		}
		if(strlen($word)>10)
		{
			$longArticles = Array("وبال","فبال");
			$temp = substr($word, 0, 8);
			if(in_array($temp,$longArticles))
			{
				return substr($word, 8, strlen($word)-8);
			}
		}
		$result = $word;
		return $result;
	}
	//------------------------------------------------------------------------------------------------
	public function removeVerbPrefix($word) {
		if(strlen($word)>6)
		{
			$prefixes = Array("ست","سي","سن","سا");
			$temp = substr($word, 0, 4);
			if(in_array($temp,$prefixes))
			{
				return substr($word, 4, strlen($word)-4);
			}
		}
		$result = $word;
		return $result;
	}
	//------------------------------------------------------------------------------------------------
	public function removeGeneralPrefix($word) {
		if(strlen($word)>4)
		{
			$shortPrefixes = Array("و");
			$temp = substr($word, 0, 2);
			if(in_array($temp,$shortPrefixes))
			{
				return substr($word, 2, strlen($word)-2);
			}
		}
		if(strlen($word)>6)
		{
			$mediumPrefixes = Array("فب","فل","فك","وب","ول","وك");
			$temp = substr($word, 0, 4);
			if(in_array($temp,$mediumPrefixes))
			{
				return substr($word, 4, strlen($word)-4);
			}
		}
		$result = $word;
		return $result;
	}
	//------------------------------------------------------------------------------------------------
	public function removeSubjectPronounSuffix($word)
	{
		if(strlen($word)>8)
		{
			$longSuffixes = Array("يان","تان","يون","يين","يات");
			$temp = substr($word, strlen($word)-6, 6);
			if(in_array($temp,$longSuffixes))
			{
				return substr($word, 0, strlen($word)-6);
			}
		}
		if(strlen($word)>6)
		{
			$shortSuffixes = Array("ية","ين","ات","ون","ان");
			$temp = substr($word, strlen($word)-4, 4);
			if(in_array($temp,$shortSuffixes))
			{
				return substr($word, 0, strlen($word)-4);
			}
		}
		if(strlen($word)>4)
		{
			$oneLetterSuffixes = Array("ي","ة");
			$temp = substr($word, strlen($word)-2, 2);
			if(in_array($temp,$oneLetterSuffixes))
			{
				return substr($word, 0, strlen($word)-2);
			}
		}
		$result = $word;
		return $result;
	}
	//-------------------------------------------------------------------------------------------------------------
	public function removeObjectPronounSuffix($word)
	{
		if(strlen($word)>8)
		{
			$longSuffixes = Array("كما","هما");
			$temp = substr($word, strlen($word)-6, 6);
			if(in_array($temp,$longSuffixes))
			{
				return substr($word, 0, strlen($word)-6);
			}
		}
		if(strlen($word)>6)
		{
			$shortSuffixes = Array("كم","كن","هم","ها","هن","نا","ني");
			$temp = substr($word, strlen($word)-4, 4);
			if(in_array($temp,$shortSuffixes))
			{
				return substr($word, 0, strlen($word)-4);
			}
		}
		if(strlen($word)>4)
		{
			$oneLetterSuffixes = Array("ه");
			$temp = substr($word, strlen($word)-2, 2);
			if(in_array($temp,$oneLetterSuffixes))
			{
				return substr($word, 0, strlen($word)-2);
			}
		}
		$result = $word;
		return $result;
	}
}
?>