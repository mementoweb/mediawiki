<?php

# ensure that the script can't be executed outside of Mediawiki
if ( ! defined( 'MEDIAWIKI' ) ) {
	echo "Not a valid entry point";
	exit( 1 );
}

$aliases = array();

/** English */
$aliases['en'] = array(
	'TimeGate' => array( 'TimeGate' ),
);
