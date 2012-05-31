<?php

//this extension need not be run in command line mode: usually for maintanence.
if( $wgCommandLineMode ) {
    return true;
}

//timegate, timemaps and timebundles are included
require_once "$IP/extensions/memento/timegate/timegate.php";
require_once "$IP/extensions/memento/timemap/timemap.php";

$wgExtensionFunctions[] = 'mmSetupExtension';
$wgExtensionCredits['specialpage'][] = array(
        'name' => 'Special:Memento',
        'description' => 'Retrieve archived versions of the article using HTTP datetime headers.',
	    'url' => 'http://www.mediawiki.org/wiki/Extension:Memento',
        'author' => 'Harihar Shankar, Herbert Van de Sompel, Robert Sanderson',
        'version' => '1.0',
        );
$wgExtensionMessagesFiles['memento'] = dirname( __FILE__ ) . '/memento.i18n.php';


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

    if( isset($last['uri']) && $last['uri'] == $uri ) {
        $rel .= " last";
        unset($last);
    }
    if( isset($prev['uri']) && $prev['uri'] == $uri ) {
        $rel .= " prev predecessor-version";
        unset($prev);
    }
    elseif( isset($mem['uri']) && $mem['uri'] == $uri ) {
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

        if( isset($mem['uri']) && $mem['uri'] == $uri ) {
            $rel .= " memento";
            $mflag = true;
            unset($mem);
        }
        elseif( isset($next['uri']) && $next['uri'] == $uri ) {
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



function mmAcceptDateTime() {
    global $wgArticlePath;
    global $wgServer;
    global $wgRequest;

    $requestURL = $wgRequest->getRequestURL();
    $waddress = preg_replace( '/\/\$1/', '', $wgArticlePath );
    $tgURL = SpecialPage::getTitleFor('TimeGate')->getPrefixedText();

    $context = new RequestContext();
    $objTitle = $context->getTitle();
    $title = preg_replace('/ /', "_", $objTitle->getPrefixedText());


    //Making sure the header is checked only in the main article.  
    if( !isset($_GET['oldid']) && !$objTitle->isSpecialPage() ) {
        $uri='';
        $uri = wfExpandUrl($waddress . "/" . $tgURL) . "/" . wfExpandUrl($requestURL);

        $mementoResponse = $wgRequest->response();
        $mementoResponse->header( 'Link: <' . $uri . ">; rel=\"timegate\"" );
    }
    elseif( isset($_GET['oldid']) ) {
        $last = array(); $first = array(); $next = array(); $prev = array(); $mem = array();

        //creating a db object to retrieve the old revision id from the db. 
        $dbr = wfGetDB( DB_SLAVE );
        $dbr->begin();

        $oldid = intval($_GET['oldid']);

        $res_pg = $dbr->select( 
                                'revision', 
                                array('rev_page', 'rev_timestamp'), 
                                array("rev_id=$oldid"), 
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

        // previous version
        $xares = $dbr->select( 
                                'revision', 
                                array('rev_id', 'rev_timestamp'), 
                                array("rev_page=$pg_id","rev_timestamp<$pg_ts"), 
                                __METHOD__, 
                                array('DISTINCT', 'ORDER BY'=>'rev_timestamp DESC', 'LIMIT'=>'1') 
                            );

        if( $xarow = $dbr->fetchObject( $xares ) ) {
            $prevRevID = $xarow->rev_id;
            $prevRevTS = $xarow->rev_timestamp;
            $prevRevTS = wfTimestamp( TS_RFC2822,  $prevRevTS );

            $prev['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$prevRevID) );
            $prev['dt'] = $prevRevTS;
        }

        // first version
        $xares = $dbr->select( 
                                'revision', 
                                array('rev_id', 'rev_timestamp'), 
                                array("rev_page=$pg_id","rev_timestamp<=$pg_ts"), 
                                __METHOD__, 
                                array('DISTINCT', 'ORDER BY'=> 'rev_timestamp ASC', 'LIMIT'=>'1') 
                            );

        if( $xarow = $dbr->fetchObject( $xares ) ) {

            $oldestRevID = $xarow->rev_id;
            $oldestRevTS = $xarow->rev_timestamp;
            $oldestRevTS = wfTimestamp( TS_RFC2822,  $oldestRevTS );

            $first['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$oldestRevID) );
            $first['dt'] = $oldestRevTS;
        }

        // last version
        $xares = $dbr->select( 
                                'revision', 
                                array('rev_id', 'rev_timestamp'), 
                                array("rev_page=$pg_id","rev_timestamp>=$pg_ts"), 
                                __METHOD__, 
                                array('DISTINCT', 'ORDER BY'=> 'rev_timestamp DESC', 'LIMIT'=>'1') 
                            );

        if( $xarow = $dbr->fetchObject( $xares ) ) {
            $recentRevID = $xarow->rev_id;
            $recentRevTS = $xarow->rev_timestamp;
            $recentRevTS = wfTimestamp( TS_RFC2822,  $recentRevTS );

            $last['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$recentRevID) );
            $last['dt'] = $recentRevTS;
        }

        // next version
        $xares = $dbr->select( 
                                'revision', 
                                array('rev_id', 'rev_timestamp'), 
                                array("rev_page=$pg_id","rev_timestamp>$pg_ts"), 
                                __METHOD__, 
                                array('DISTINCT', 'ORDER BY'=> 'rev_timestamp ASC', 'LIMIT'=>'1') 
                            );

        if( $xarow = $dbr->fetchObject( $xares ) ) {
            $nextRevID = $xarow->rev_id;
            $nextRevTS = $xarow->rev_timestamp;
            $nextRevTS = wfTimestamp( TS_RFC2822,  $nextRevTS );

            $next['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$nextRevID) );
            $next['dt'] = $nextRevTS;
        }


        //original version in the link header... 
        $link = "<" . wfExpandUrl( $waddress . '/' . $title ).">; rel=\"original latest-version\", ";
        $link .= "<" . wfExpandUrl( $waddress . "/" . $tgURL ) . "/" . wfExpandUrl( $waddress . "/" . $title ) .">; rel=\"timegate\", ";
        $link .= "<" . wfExpandUrl( $waddress . "/" . SpecialPage::getTitleFor('TimeMap') ) . "/" . wfExpandUrl( $waddress . "/" . $title ) .">; rel=\"timemap\"; type=\"application/link-format\"";


        $pg_ts = wfTimestamp( TS_RFC2822, $pg_ts );

        $mem['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$oldid) );
        $mem['dt'] = $pg_ts;

        $header = array( 
                    "Link" =>  mmConstructLinkHeader( $first, $last, $mem, $next, $prev ) . $link,
                    "Memento-Datetime" => $pg_ts );

        mmSend(200, $header, null);

    }
    return true;
}
