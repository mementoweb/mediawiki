<?php

//this extension need not be run in command line mode, usually used for maintanence.
if ( $wgCommandLineMode ) {
	return true;
}

//timegate, timemaps and timebundles are included
require_once "$IP/extensions/memento/timegate/timegate.php";
require_once "$IP/extensions/memento/timemap/timemap.php";

$wgExtensionCredits['specialpage'][] = array(
		'name' => 'Special:Memento',
		'description' => 'Retrieve archived versions of an article using HTTP datetime headers.',
		'url' => 'http://www.mediawiki.org/wiki/Extension:Memento',
		'author' => array('Harihar Shankar', 'Herbert Van de Sompel', 'Robert Sanderson'),
		'version' => '1.0',
		);
$wgExtensionMessagesFiles['memento'] = dirname( __FILE__ ) . '/memento.i18n.php';
global $wgHooks;
$wgHooks['BeforePageDisplay'][] = 'mmAcceptDateTime';



/**
 * Constructs and returns a string with urls and rel types as defined in the memento RFC.
 * The constructed string is compatible with the link header format. 
 * Checks and concats rel types, if the url passed in the different parameters are same. 
 *
 * @param $first: associative array, not optional.
 *      Contains url and datetime info for the first memento of a resource. 
 *      $first['uri'] is the url of the first memento. 
 *      $first['dt'] is the datetime of the first memento. 
 * @param $last: associative array, not optional.
 *      Contains url and datetime info for the last memento of a resource. 
 *      $last['uri'] is the url of the last memento. 
 *      $last['dt'] is the datetime of the last memento. 
 * @param $mem: associative array, optional.
 *      Contains url and datetime info for the memento of a resource. 
 *      $mem['uri'] is the url of the memento. 
 *      $mem['dt'] is the datetime of the memento. 
 * @param $next: associative array, optional.
 *      Contains url and datetime info for the next memento of a resource. 
 *      $next['uri'] is the url of the next memento. 
 *      $next['dt'] is the datetime of the next memento. 
 * @param $prev: associative array, optional.
 *      Contains url and datetime info for the prev memento of a resource. 
 *      $prev['uri'] is the url of the prev memento. 
 *      $prev['dt'] is the datetime of the prev memento. 
 * @return String, the constructed link header.
 */

function mmConstructLinkHeader( $first, $last, $mem='', $next='', $prev='' ) {
	$dt = $first['dt'];
	$uri = $first['uri'];
	$mflag = false;
	$rel = "first";

	if ( isset( $last['uri'] ) && $last['uri'] == $uri ) {
		$rel .= " last";
		unset( $last );
	}
	if ( isset( $prev['uri'] ) && $prev['uri'] == $uri ) {
		$rel .= " prev predecessor-version";
		unset( $prev );
	}
	elseif ( isset( $mem['uri'] ) && $mem['uri'] == $uri ) {
		$rel .= " memento";
		$mflag = true;
		unset( $mem );
	}

	if ( !$mflag )
		$rel .= " memento";
	$link = "<$uri>;rel=\"$rel\";datetime=\"$dt\", ";

	if ( $last ) {
		$dt = $last['dt'];
		$uri = $last['uri'];
		$rel = "last";
		$mflag = false;

		if ( isset( $mem['uri'] ) && $mem['uri'] == $uri ) {
			$rel .= " memento";
			$mflag = true;
			unset( $mem );
		}
		elseif ( isset( $next['uri'] ) && $next['uri'] == $uri ) {
			$rel .= " next successor-version";
			unset( $next );
		}
		if ( !$mflag )
			$rel .= " memento";
		$link .= "<$uri>;rel=\"$rel\";datetime=\"$dt\", ";
	}

	if ( isset( $prev['uri'] ) )
		$link .= "<" . $prev['uri'] . ">;rel=\"prev predecessor-version memento\";datetime=\"" . $prev['dt'] . "\", ";
	if ( isset( $next['uri'] ) )
		$link .= "<" . $next['uri'] . ">;rel=\"next successor-version memento\";datetime=\"" . $next['dt'] . "\", ";
	if ( isset( $mem['uri'] ) )
		$link .= "<" . $mem['uri'] . ">;rel=\"memento\";datetime=\"" . $mem['dt'] . "\", ";

	return $link;
}


/**
 * Prepares and sends HTTP responses in memento mode. 
 * Used mainly to send 30*, 40* and 50* HTTP error codes that the mediawiki api 
 * does not expose. 
 * @param $statusCode: number, optional, default is 200.
 *      The HTTP error code to send. 302, 404, 503, etc. 
 * @param $headers: associative array, optional.
 *      A list of key->value pairs to be sent with the HTTP Response headers.
 *      The HTTP header name is the key, and the header value is the value of the key.
 * @param $msg: String, optional.
 *      A message to be sent with the HTTP response.
 *      eg: "Error 404: The requested resource is not found!"
 */
function mmSend( $statusCode=200, $headers=array(), $msg=null ) {
	global $wgRequest, $wgOut;
	$mementoResponse = $wgRequest->response();

	if ( $statusCode != 200 ) {
		$mementoResponse->header( "HTTP", TRUE, $statusCode );
	}

	if ( is_array( $headers ) )
		foreach ( $headers as $name => $value )
			$mementoResponse->header( "$name: $value" );

	if ( $msg != null ) {
		$wgOut->disable();
		echo $msg;
	}
}


/**
 * Fetches the appropriate revision for a resource from the database,
 * constructs the memento url, and the memento datetime in RFC2822 format. 
 * 
 * @param: $relType: String, not optional.
 *      The value of the memento rel type; first, last, next, prev, memento.
 *      The rel type determines the direction of the sql query. 
 * @param: $pg_id: number, not optional.
 *      The page id of a resource.
 * @param: $pg_ts: unix datetime, not optional.
 *      The datetime value of the requested memento.
 *      This is usually the value of the accept-datetime value. 
 * @param: db_details: associative array, not optional.
 *      Miscellaneous details needed to query the db and construct the urls. 
 *      db_details['dbr'] is the cursor to the db handler.
 *      db_details['title'] is the title of the resource.
 *      db_details['waddress'] is the url template to construct the memento urls.
 * @return: associative array.
 */
function mmFetchMementoFor( $relType, $pg_id, $pg_ts, $db_details ) {

	$dbr = $db_details['dbr'];
	$title = $db_details['title'];
	$waddress = $db_details['waddress'];


	if ( !isset( $pg_id ) ) {
		return array();
	}

	$rev = array();

	switch ( $relType ) {
		case 'first':
			if ( isset( $pg_ts ) )
				$sqlCond = array( "rev_page=$pg_id", "rev_timestamp<=$pg_ts" );
			else
				$sqlCond = array( "rev_page=$pg_id" );
			$sqlOrder = "rev_timestamp ASC";
			break;
		case 'last':
			if ( isset( $pg_ts ) )
				$sqlCond = array( "rev_page=$pg_id", "rev_timestamp>=$pg_ts" );
			else
				$sqlCond = array( "rev_page=$pg_id" );
			$sqlOrder = "rev_timestamp DESC";
			break;
		case 'next':
			if ( !isset( $pg_ts ) ) {
				return array();
			}
			$sqlCond = array( "rev_page=$pg_id", "rev_timestamp>$pg_ts" );
			$sqlOrder = "rev_timestamp ASC";
			break;
		case 'prev':
			if ( !isset( $pg_ts ) ) {
				return array();
			}
			$sqlCond = array( "rev_page=$pg_id", "rev_timestamp<$pg_ts" );
			$sqlOrder = "rev_timestamp DESC";
			break;
		case 'memento':
			if ( !isset( $pg_ts ) ) {
				return array();
			}
			$sqlCond = array( "rev_page=$pg_id", "rev_timestamp<=$pg_ts" );
			$sqlOrder = "rev_timestamp DESC";
			break;
		default:
			return array();
	}


	$xares = $dbr->select( 
			'revision', 
			array( 'rev_id', 'rev_timestamp' ), 
			$sqlCond, 
			__METHOD__, 
			array( 'ORDER BY'=>$sqlOrder, 'LIMIT'=>'1' ) 
			);

	if( $xarow = $dbr->fetchObject( $xares ) ) {
		$revID = $xarow->rev_id;
		$revTS = $xarow->rev_timestamp;
		$revTS = wfTimestamp( TS_RFC2822,  $revTS );

		$rev['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array( "title"=>$title, "oldid"=>$revID ) );
		$rev['dt'] = $revTS;
	}

	return $rev;
}



/** The main hook for the plugin.
 * Appends a link header with the timegate link to the article pages. 
 * Appends memento link headers if the revision of an article is loaded.
 *
 * @return: Bool.
 */

function mmAcceptDateTime() {
	global $wgArticlePath;
	global $wgServer;
	global $wgRequest;
	global $wgMementoExcludeNamespaces;

	$requestURL = $wgRequest->getRequestURL();
	$waddress = str_replace( '/$1', '', $wgArticlePath );
	$tgURL = SpecialPage::getTitleFor( 'TimeGate' )->getPrefixedText();

	$context = new RequestContext();
	$objTitle = $context->getTitle();
	$title = $objTitle->getPrefixedURL();
	$title = urlencode( $title );


	//Making sure the header is checked only in the main article.  
	if ( !isset( $_GET['oldid'] ) && !$objTitle->isSpecialPage() && !in_array( $objTitle->getNamespace(), $wgMementoExcludeNamespaces ) ) {
		$uri='';
		$uri = wfExpandUrl( $waddress . "/" . $tgURL ) . "/" . wfExpandUrl( $requestURL );

		$mementoResponse = $wgRequest->response();
		$mementoResponse->header( 'Link: <' . $uri . ">; rel=\"timegate\"" );
	}
	elseif ( isset( $_GET['oldid'] ) ) {
		$last = array(); $first = array(); $next = array(); $prev = array(); $mem = array();

		//creating a db object to retrieve the old revision id from the db. 
		$dbr = wfGetDB( DB_SLAVE );
		$dbr->begin();

		$oldid = intval( $_GET['oldid'] );

		$res_pg = $dbr->select( 
				'revision', 
				array( 'rev_page', 'rev_timestamp' ), 
				array( "rev_id=$oldid" ), 
				__METHOD__, 
				array() 
				);

		if ( !$res_pg ) {
			return true;
		}

		$row_pg = $dbr->fetchObject( $res_pg );
		$pg_id = $row_pg->rev_page;
		$pg_ts = $row_pg->rev_timestamp;

		if( $pg_id <= 0 ) {
			return true;
		}

		$db_details = array( 'dbr'=>$dbr, 'title'=>$title, 'waddress'=>$waddress );

		// prev/next/last/first versions
		$prev = mmFetchMementoFor( 'prev', $pg_id, $pg_ts, $db_details );
		$next = mmFetchMementoFor( 'next', $pg_id, $pg_ts, $db_details );
		$last = mmFetchMementoFor( 'last', $pg_id, $pg_ts, $db_details );
		$first = mmFetchMementoFor( 'first', $pg_id, $pg_ts, $db_details );

		//original version in the link header... 
		$link = "<" . wfExpandUrl( $waddress . '/' . $title ) . ">; rel=\"original latest-version\", ";
		$link .= "<" . wfExpandUrl( $waddress . "/" . $tgURL ) . "/" . wfExpandUrl( $waddress . "/" . $title ) . ">; rel=\"timegate\", ";
		$link .= "<" . wfExpandUrl( $waddress . "/" . SpecialPage::getTitleFor('TimeMap') ) . "/" . wfExpandUrl( $waddress . "/" . $title ) . ">; rel=\"timemap\"; type=\"application/link-format\"";


		$pg_ts = wfTimestamp( TS_RFC2822, $pg_ts );

		$mem['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array( "title"=>$title, "oldid"=>$oldid ) );
		$mem['dt'] = $pg_ts;

		$header = array( 
				"Link" =>  mmConstructLinkHeader( $first, $last, $mem, $next, $prev ) . $link,
				"Memento-Datetime" => $pg_ts );

		$dbr->commit();
		mmSend( 200, $header, null );

	}
	return true;
}
