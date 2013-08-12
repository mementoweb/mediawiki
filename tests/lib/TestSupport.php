<?php

function acquireLinesFromFile($filename) {
	$data = array();

	$lines = file($filename);

	foreach ($lines as $line) {

		$line = trim($line);

		$cur = array (
			$line
		);

		array_push($data, $cur);
	}

	return $data;
}

function acquireCSVDataFromFile($filename, $columns) {
	$data = array();
	$counter = 0;

	$lines = file($filename);

	foreach ($lines as $line) {

		if ($counter != 0) {
	
			$filedata = str_getcsv($line);
	
			$cur = array();
	
			for ($i = 0; $i < $columns; $i++) {
				$tmp = $filedata[$i];
				array_push($cur, $tmp);
			}
	
			array_push($data, $cur);
		}

		$counter++;
	}

	return $data;
}

function acquireFormattedI18NString($lang, $key) {

	if (!defined('MEDIAWIKI')) {
		define("MEDIAWIKI", true);
	}

	require('Memento.i18n.php');

	$format = $messages[$lang][$key];
	$format = str_replace(array('$1', '$2', '$3'), '%s', $format);

	return $format;
}

function diffStrings($string1, $string2) {

	$s1len = strlen($string1);
	$s2len = strlen($string2);

	if ( $s1len > $s2len ) {
		$size = $s1len;
		$limit = $s2len;
		$overage = "string1";
	} else {
		$size = $s2len;
		$limit = $s2len;
		$overage = "string2";
	}

	for ( $i = 0; $i < $size; $i++ ) {

		if ($i < $limit) {
	
			if ( $string1[$i] == $string2[$i] ) {
				echo "strings match at position $i with character " . $string1[$i] . "\n";
			} else {
				echo "at position $i: string1 has character " . $string1[$i] . 
					'(' . ord($string1[$i]) . "), and string2 has character " .
					$string2[$i] .  '(' . ord($string2[$i]) . ")\n";
			}

		} else {
			echo "$overage[$i] has character " . ord(eval("$overage\[\$i\]")) . "\n";
		}

	}

}

?>
