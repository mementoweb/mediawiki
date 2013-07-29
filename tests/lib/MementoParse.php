<?php

/*
 * Given the string $linkvalues, which is the value part of the Link: header,
 * return an array of key-value pairs for easier access, but only the ones
 * that correspond to the Memento standard.
 *
 * Note:  str_getcsv doesn't work because the Link header isn't actually in 
 *		the CSV format:  
 *			<url>;rel="something";datetime="somethingwith ,",<url>...
 *		because datetime="something with ," 
 *		instead of "datetime=something with ,"
 *
 * This function had to use regex instead, which limits it to just the memento
 * items.
 *
 */
function extractItemsFromLink($linkvalues) {

    $relations = array();

    $datetime = NULL;
    $item = NULL;

	preg_match_all('/<([^>]*)>;[ ]*rel="timegate"/', $linkvalues, $matches);

	if ( count($matches[0]) > 0 ) {
		$relations['timegate']['url'] = $matches[1][0];
	}

	preg_match_all('/<([^>]*)>;[ ]*rel="timemap"/', $linkvalues, $matches);

	if ( count($matches[0]) > 0 ) {
		$relations['timemap']['url'] = $matches[1][0];
	}

	preg_match_all('/<([^>]*)>;[ ]*rel="original latest-version"/', $linkvalues, $matches);

	if ( count($matches[0]) > 0 ) {
		$relations['original latest-version']['url'] = $matches[1][0];
	}

	preg_match_all('/<([^>]*)>;[ ]*rel="original timegate"/', $linkvalues, $matches);

	if ( count($matches[0]) > 0 ) {
		$relations['original timegate']['url'] = $matches[1][0];
	}

	// get the 'normal' memento link entries
	preg_match_all('/<([^>]*)>;[ ]*rel="([^"]*)";[ ]*datetime="([^"]*)"/',
		$linkvalues, $matches);

	if ( count($matches[0]) > 0 ) {

		for ( $i = 0; $i < count($matches[0]); $i++ ) {

			$url = $matches[1][$i];
			$rel = $matches[2][$i];
			$datetime = $matches[3][$i];

			$relations[$rel]['url'] = $url;
			$relations[$rel]['datetime'] = $datetime;

		}
	}

    return $relations;    
}

function extractItemsFromVary($varyValues) {
    
    $items = explode(',', $varyValues);

    $varyItems = array();

    foreach ($items as $item) {
        $item = trim($item);
        array_push($varyItems, $item);
    }

    return $varyItems;
}

?>
