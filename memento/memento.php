<?php

//this extension need not be run in command line mode: usually for maintanence.
if( $wgCommandLineMode ) {
    return true;
}

//timegate, timemaps and timebundles are included
require_once "$IP/extensions/memento/timegate/timegate.php";
require_once "$IP/extensions/memento/timemap/timemap.php";

$mmScriptPath = $wgScriptPath . '/extensions/memento';
$wgExtensionFunctions[] = 'mmSetupExtension';
$wgExtensionCredits['specialpage'][] = array(
        'name' => 'Special:Memento',
        'description' => 'Retrieve archived versions of the article using HTTP datetime headers.',
	    'url' => 'http://www.mediawiki.org/wiki/Extension:Memento',
        'author' => 'Harihar Shankar, Herbert Van de Sompel, Robert Sanderson',
        'version' => '0.7',
        );
$wgExtensionMessagesFiles['memento'] = dirname( __FILE__ ) . '/memento.i18n.php';

$historyuri;

function mmSetupExtension() {
    global $wgHooks;

    $wgHooks['BeforePageDisplay'][] = 'mmAcceptDateTime';

    return true;
}

function mmConstructLinkHeader( $first, $last, $mem='', $next='', $prev='' ) {
    $dt = $first['dt'];
    $uri = $first['uri'];
    $mflag = false;
    $rel = "first";

    if( $last && $last['uri'] == $uri ) {
        $rel .= " last";
        unset($last);
    }
    if( $prev && $prev['uri'] == $uri ) {
        $rel .= " prev predecessor-version";
        unset($prev);
    }
    elseif( $mem && $mem['uri'] == $uri ) {
        $rel .= " memento";
        $mflag = true;
        unset($mem);
    }

    if( !$mflag )
        $rel .= " memento";
    $link = "<$uri>;rel=\"$rel\";datetime=\"$dt\", ";

    if( $last ) {
        $dt = $last['dt'];
        $uri = $last['uri'];
        $rel = "last";
        $mflag = false;

        if( $mem && $mem['uri'] == $uri ) {
            $rel .= " memento";
            $mflag = true;
            unset($mem);
        }
        elseif( $next && $next['uri'] == $uri ) {
            $rel .= " next successor-version";
            unset($next);
        }
        if( !$mflag )
            $rel .= " memento";
        $link .= "<$uri>;rel=\"$rel\";datetime=\"$dt\", ";
    }

    if( isset($prev['uri']) )
        $link .= "<".$prev['uri'].">;rel=\"prev predecessor-version memento\";datetime=\"".$prev['dt']."\", ";
    if( isset($next['uri']) )
        $link .= "<".$next['uri'].">;rel=\"next successor-version memento\";datetime=\"".$next['dt']."\", ";
    if( isset($mem['uri']) )
        $link .= "<".$mem['uri'].">;rel=\"memento\";datetime=\"".$mem['dt']."\", ";

    return $link;
}


function mmSend( $statusCode=200, $headers=array(), $msg=null ) {
    global $wgRequest, $wgOut;
    $mementoResponse = $wgRequest->response();

    if( $statusCode != 200 ) {
        $mementoResponse->header( "HTTP", TRUE, $statusCode );
    }

    foreach( $headers as $name => $value )
        $mementoResponse->header( "$name: $value" );

    if( $msg != null ) {
        $wgOut->disable();
        echo $msg;
    }
}


function mmConvertTimestamp( $timestamp ) {
    $year = substr($timestamp, 0, 4);
    $month = substr($timestamp, 4, 2);
    $day = substr($timestamp, 6, 2);
    $hour = substr($timestamp, 8, 2);
    $minute = substr($timestamp, 10, 2);
    $second = substr($timestamp, 12, 2);
    $timestamp = gmdate('D, d M Y H:i:s T', mktime($hour, $minute, $second, $month, $day, $year));
    return $timestamp;
}

function mmAcceptDateTime() {
    global $wgTitle;
    global $wgMementoNamespace;
    global $wgArticlePath;
    global $wgServer;
    global $wgRequest;

    $Server = $wgServer;

    $requestURL = $wgRequest->getRequestURL();

    //Making sure the header is checked only in the main title page.  
    if( !stripos( $requestURL, '?' ) && !stripos( $requestURL, 'Special:TimeGate' ) ) {
        $timegate; $parameter; $uri;

        //getting the namespaceid. 
        $wgMementoNamespace = $wgTitle->getNamespace();

        $waddress = preg_replace( '/\$1/', '', $wgArticlePath );

        $timegate = $Server . $waddress . "Special:TimeGate/";
        $parameter = $Server . $requestURL;

        $uri = $timegate . $parameter;


        $mementoResponse = $wgRequest->response();
        $mementoResponse->header( 'Link: <' . $uri . ">; rel=\"timegate\"" );
    }
    elseif( stripos( $requestURL, 'oldid=' ) ) {
        $last = array(); $first = array(); $next = array(); $prev = array(); $mem = array();

        date_default_timezone_set('GMT');

        //creating a db object to retrieve the old revision id from the db. 
        $dbr = wfGetDB( DB_SLAVE );
        $dbr->begin();

        $param = explode( 'oldid=', $requestURL );
        $oldid = intval($param[1]);

        $res_pg = $dbr->select( 'revision', array('rev_page', 'rev_timestamp'), array("rev_id=$oldid"), __METHOD__, array() );

        if ( !$res_pg ) {
            return true;
        }

        $row_pg = $dbr->fetchObject( $res_pg );
        $pg_id = $row_pg->rev_page;
        $pg_ts = $row_pg->rev_timestamp;

        if( $pg_id <= 0 ) {
            return true;
        }

        $alt_header = '';
        //getting the title of the page from the request uri
        $requri = explode( "title=", $requestURL );
        $t = explode( "&", $requri[1] );
        $title = $t[0];

        $alturi = $Server; 

        $wikiaddr = explode( "?", $requestURL );
        $alturi .= $wikiaddr[0];

        #if( substr($alturi, -1) != '/' )
        #    $alturi .= '/';

        $xares = $dbr->select( 'revision', array('rev_id', 'rev_timestamp'), array("rev_page=$pg_id"), __METHOD__, array('DISTINCT', 'ORDER BY'=>'rev_id DESC') );

        while( $xarow = $dbr->fetchObject( $xares ) ) {
            $revTS[] = $xarow->rev_timestamp;
            $revID[] = $xarow->rev_id;
        }

        $cnt = count($revTS);

        //the most recent version's timestamp and id.
        $recentRevID = $revID[0];
        $recentRevTS = $revTS[0];
        $recentRevTS = mmConvertTimestamp( $recentRevTS );

        $last['uri'] = $alturi . "?title=" . $title . "&oldid=" . $recentRevID;
        $last['dt'] = $recentRevTS;

        //the oldest version's timestamp and id.
        $oldestRevID = $revID[$cnt-1];
        $oldestRevTS = $revTS[$cnt-1];
        $oldestRevTS = mmConvertTimestamp( $oldestRevTS );

        $first['uri'] = $alturi . "?title=" . $title . "&oldid=" . $oldestRevID;
        $first['dt'] = $oldestRevTS;

        for( $i=0; $i<$cnt; $i++ ) 
            if( $pg_ts == $revTS[$i] )
                break;

        //previous version's timestamp and id. 
        if( $revTS[$i+1] ) {
            $prevRevID = $revID[$i+1];
            $prevRevTS = $revTS[$i+1]; #The timestamps are arranged in descending order!
            $prevRevTS = mmConvertTimestamp( $prevRevTS );

            $prev['uri'] = $alturi . "?title=" . $title . "&oldid=" . $prevRevID;
            $prev['dt'] = $prevRevTS;
        }

        //next version's timestamp and id.
        if( $i-1 >= 0 && $revTS[$i-1] ) {
            $nextRevID = $revID[$i-1];
            $nextRevTS = $revTS[$i-1];
            $nextRevTS = mmConvertTimestamp( $nextRevTS );

            $next['uri'] = $alturi . "?title=" . $title . "&oldid=" . $nextRevID;
            $next['dt'] = $nextRevTS;
        }

        //original version in the link header... 
        $link = "<" . $alturi . "/" . $title .">; rel=\"original latest-version\", ";
        $link .= "<" . $alturi."/Special:TimeGate/".$alturi . "/" . $title .">; rel=\"timegate\", ";
        $link .= "<" . $alturi."/Special:TimeMap/".$alturi . "/" . $title .">; rel=\"timemap\"; type=\"application/link-format\"";


        $pg_ts = mmConvertTimestamp( $pg_ts );

        $mem['uri'] = $alturi . "?title=" . $title . "&oldid=" . $oldid;
        $mem['dt'] = $pg_ts;

        $header = array( 
                    "Link" =>  mmConstructLinkHeader( $first, $last, $mem, $next, $prev ) . $link,
                    "Memento-Datetime" => $pg_ts );

        mmSend(200, $header, null);

    }
    return true;
}
