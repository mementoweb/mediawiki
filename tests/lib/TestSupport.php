<?php

function acquireLinesFromFile( $filename ) {
	$data = [];

	$lines = file( $filename );

	foreach ( $lines as $line ) {
		$line = trim( $line );

		$cur = [
			$line
		];

		array_push( $data, $cur );
	}

	return $data;
}

function acquireCSVDataFromFile( $filename, $columns ) {
	$data = [];
	$counter = 0;

	$lines = file( $filename );

	foreach ( $lines as $line ) {
		if ( $counter != 0 ) {
			$filedata = str_getcsv( $line );

			$cur = [];

			for ( $i = 0; $i < $columns; $i++ ) {
				$tmp = $filedata[$i];
				array_push( $cur, $tmp );
			}

			array_push( $data, $cur );
		}

		$counter++;
	}

	return $data;
}

function objectToArray( $d ) {
	// shamelessly stolen from:
	// http://www.if-not-true-then-false.com/2009/php-tip-convert-stdclass-object-to-multidimensional-array-and-convert-multidimensional-array-to-stdclass-object/
	if ( is_object( $d ) ) {
		// Gets the properties of the given object
		// with get_object_vars function
		$d = get_object_vars( $d );
	}

	if ( is_array( $d ) ) {
		/*
		* Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return array_map( __FUNCTION__, $d );
	} else {
		// Return array
		return $d;
	}
}

function acquireFormattedI18NString( $lang, $key ) {
	if ( !defined( 'MEDIAWIKI' ) ) {
		define( "MEDIAWIKI", true );
	}

	$filename = "../../Memento/i18n/$lang.json";

	$handle = fopen( $filename, 'r' );

	$json = fread( $handle, filesize( $filename ) );

	fclose( $handle );

	$messages = json_decode( $json );

	$messages = objectToArray( $messages );

	$format = $messages[$key];
	$format = str_replace( [ '$1', '$2', '$3' ], '%s', $format );

	return $format;
}

function diffStrings( $string1, $string2 ) {
	$s1len = strlen( $string1 );
	$s2len = strlen( $string2 );

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
		if ( $i < $limit ) {
			if ( $string1[$i] == $string2[$i] ) {
				echo "strings match at position $i with character " . $string1[$i] . "\n";
			} else {
				echo "at position $i: string1 has character " . $string1[$i] .
					'(' . ord( $string1[$i] ) . "), and string2 has character " .
					$string2[$i] . '(' . ord( $string2[$i] ) . ")\n";
			}

		} else {
			echo "$overage[$i] has character " . ord( eval( "$overage\[\$i\]" ) ) . "\n";
		}

	}
}
