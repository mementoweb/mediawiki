<?php
class TimeGate extends SpecialPage
{
    function TimeGate() {
        parent::__construct("TimeGate");
    }


    function execute( $par ) {

        global $wgRequest, $wgOut;
        global $wgMementoConfigDeleted;
        global $wgMementoReqDateTime;
        global $wgArticlePath;
        global $wgServer;

        $this->setHeaders();
        wfLoadExtensionMessages( 'TimeGate' );

        $requestURL = $wgRequest->getRequestURL();
        $mementoResponse = $wgRequest->response();

        if( !$par ) {
            $wgOut->addHTML(wfMsg('timegate-welcome-message'));
            return;
        }

        if( $_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'HEAD' ) {
            $header = array(
                    "Allow" => "GET, HEAD",
                    "Vary" => "negotiate, accept-datetime"
                    );
            mmSend(405, $header, null);
            exit();
        }

        $waddress = preg_replace( '/\$1/', '', $wgArticlePath );

        // getting the title of the page from the request uri
        $title = preg_replace( "|".$wgServer.$waddress."|", "", $par );

        $waddress = preg_replace( '/\/\$1/', '', $wgArticlePath );
        $historyuri = $wgServer . $waddress;

        // creating a db object to retrieve the old revision id from the db. 
        $dbr = wfGetDB( DB_SLAVE );
        $dbr->begin();

        $page_namespace_id = 0;

        $objTitle =  Title::newFromText($title);
        $pg_id = $objTitle->getArticleID();

        $page_namespace_id = $objTitle->getNamespace();
        $new_title = preg_replace("/ /", '_', $objTitle->getText());

        if( $pg_id > 0 ) {
            $this->tgGetMementoForResource($pg_id, $historyuri, $title);
        }
        // if the title was not found in the page table, the archive table is checked for deleted versions of that article.
        // provided, the variable $wgMementoConfigDeleted is set to true in the LocalSettings.php file. 
        elseif ( $wgMementoConfigDeleted == true ) {
            $this->tgGetMementoForDeletedResource($new_title, $page_namespace_id);
        }    
        else {
            $msg = wfMsgForContent( 'timegate-404-title', $title );
            $header = array( "Vary" => "negotiate, accept-datetime" );
            mmSend(404, $header, $msg);
            exit();
        }
    }


    function tgGetMementoForDeletedResource($new_title, $page_namespace_id) {
        global $wgArticlePath;


        // creating a db object to retrieve the old revision id from the db. 
        $dbr = wfGetDB( DB_SLAVE );
        $dbr->begin();

        $waddress = preg_replace( '/\/\$1/', '', $wgArticlePath );

        $res_ar = $dbr->select( 
                                'archive', 
                                array('ar_timestamp'), 
                                array("ar_title='$new_title'","ar_namespace=$page_namespace_id"), 
                                __METHOD__, 
                                array('ORDER BY'=>'ar_timestamp ASC', 'LIMIT'=>'1') 
                            );

        if( $dbr->fetchObject( $res_ar ) ) {  
            // checking if a revision exists for the requested date. 
            if (  
                $res_ar_ts = $dbr->select( 
                                            'archive', 
                                            array('ar_timestamp'), 
                                            array("ar_title='$new_title'","ar_namespace=$page_namespace_id","ar_timestamp <= $dt"), 
                                            __METHOD__, 
                                            array('ORDER BY'=>'ar_timestamp DESC', "LIMIT"=>"1") 
                                        )
               ) { 
                $row_ar_ts = $dbr->fetchObject( $res_ar_ts );
                $ar_ts = $row_ar_ts->ar_timestamp;

                if ( $ar_ts ) {
                    // redirection is done to the "special page" for deleted articles. 
                    $historyuri = wfAppendQuery( wfExpandUrl($waddress), array("title"=>SpecialPage::getTitleFor('Undelete'), "target"=>$new_title, "timestamp"=>$ar_ts) );
                    $header = array( "Location" => $historyuri );
                    mmSend(302, $header, null);
                    exit();
                }
            }
        }    
        else {
            $msg = wfMsgForContent( 'timegate-404-title', $title );
            $header = array( "Vary" => "negotiate, accept-datetime" );
            mmSend(404, $header, $msg);
            exit();
        }
    }



    function tgParseRequestDateTime($first, $last, $Link) {

        global $wgRequest;

        // getting the datetime from the http header, first converting it into unix format 
        // and then into the format mediawiki understands.

        $raw_dt = $wgRequest->getHeader("ACCEPT-DATETIME");

        // looks for datetime enclosed in ""
        $req_dt = preg_replace( '/"/', '', $raw_dt ); 

        // validating date time...
        $dt = strtotime( $req_dt );

        if( !$dt ) {
            $msg = wfMsgForContent( 'timegate-400-date', $req_dt );

            $msg .= wfMsgForContent( 'timegate-400-first-memento', $first['uri'] );
            $msg .= wfMsgForContent( 'timegate-400-last-memento', $last['uri'] );

            $header = array( "Link" => mmConstructLinkHeader( $first, $last ) . $Link );
            mmSend(400, $header, $msg);
            exit();
        }
        $dt = date( 'YmdHis', $dt );
        $wgMementoReqDateTime = $dt;

        return array( $dt, $raw_dt ); 
    }




    function tgGetMementoForResource($pg_id, $historyuri, $title) {

        global $wgRequest, $wgArticlePath;

        $waddress = preg_replace( '/\/\$1/', '', $wgArticlePath );

        // creating a db object to retrieve the old revision id from the db. 
        $dbr = wfGetDB( DB_SLAVE );
        $dbr->begin();

        $alt_header = '';
        $last = array(); $first = array(); $next = array(); $prev = array(); $mem = array();

        // first version
        $xares = $dbr->select( 
                                'revision', 
                                array('rev_id', 'rev_timestamp'), 
                                array("rev_page=$pg_id"), 
                                __METHOD__, 
                                array('DISTINCT', 'ORDER BY'=> 'rev_timestamp ASC', 'LIMIT'=>'1') 
                            );

        if( $xarow = $dbr->fetchObject( $xares ) ) {
            $oldestRevID = $xarow->rev_id;
            $oldestRevUnixTS = $xarow->rev_timestamp;
            $oldestRevTS = wfTimestamp( TS_RFC2822,  $oldestRevUnixTS );

            //$first['uri'] = $alturi . "?title=" . $title . "&oldid=" . $oldestRevID;
            $first['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$oldestRevID) );
            $first['dt'] = $oldestRevTS;
        }

        // last version
        $xares = $dbr->select( 
                                'revision', 
                                array('rev_id', 'rev_timestamp'), 
                                array("rev_page=$pg_id"), 
                                __METHOD__, 
                                array('DISTINCT', 'ORDER BY'=> 'rev_timestamp DESC', 'LIMIT'=>'1') 
                            );

        if( $xarow = $dbr->fetchObject( $xares ) ) {
            $recentRevID = $xarow->rev_id;
            $recentRevUnixTS = $xarow->rev_timestamp;
            $recentRevTS = wfTimestamp( TS_RFC2822,  $recentRevUnixTS );

            //$last['uri'] = $alturi . "?title=" . $title . "&oldid=" . $recentRevID;
            $last['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$recentRevID) );
            $last['dt'] = $recentRevTS;
        }

        $Link = "<" . wfExpandUrl( $waddress . "/". $title ) .">; rel=\"original latest-version\", ";
        $Link .= "<" . wfExpandUrl( $waddress . "/" . SpecialPage::getTitleFor('TimeMap') ) ."/" . wfExpandUrl($waddress ."/". $title) .">; rel=\"timemap\"; type=\"application/link-format\"";

        $current = date( 'YmdHis', time() );


        // checking for the occurance of the accept datetime header.
        if( !$wgRequest->getHeader('ACCEPT-DATETIME') ) {

            $memuri = wfAppendQuery( wfExpandUrl($waddress), array("title"=>$title, "oldid"=>$recentRevID) );
            $mem['uri']= wfAppendQuery( wfExpandUrl($waddress), array("title"=>$title, "oldid"=>$recentRevID) );
            $mem['dt'] = $recentRevTS;

            $xares = $dbr->select( 
                                    'revision', 
                                    array('rev_id', 'rev_timestamp'), 
                                    array("rev_page=$pg_id","rev_timestamp<$recentRevUnixTS"), 
                                    __METHOD__, 
                                    array('DISTINCT', 'ORDER BY'=>'rev_timestamp DESC', 'LIMIT'=>'1') 
                                );

            if( $xarow = $dbr->fetchObject( $xares ) ) {
                $prevRevID = $xarow->rev_id;
                $prevRevTS = $xarow->rev_timestamp;
                $prevRevTS = wfTimestamp( TS_RFC2822,  $prevRevTS );

                //$prev['uri'] = $alturi . "?title=" . $title . "&oldid=" . $prevRevID;
                $prev['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$prevRevID) );
                $prev['dt'] = $prevRevTS;
            }

            $header = array( 
                    "Location" => $memuri,
                    "Vary" => "negotiate, accept-datetime",
                    "Link" => mmConstructLinkHeader( $first, $last, $mem, '', $prev ) . $Link 
                    );

            mmSend(302, $header, null);
            exit();
        }

        list($dt, $raw_dt) = $this->tgParseRequestDateTime($first, $last, $Link);

        // if the requested time is earlier than the first memento, the first memento will be returned
        //if the requested time is past the last memento, or in the future, the last memento will be returned. 
        if( $dt < $oldestRevUnixTS ) {
            $dt = $oldestRevUnixTS;
        }
        elseif( $dt > $recentRevUnixTS ) {
            $dt = $recentRevUnixTS;
        }

        // memento
        $xares = $dbr->select( 
                                'revision', 
                                array('rev_id', 'rev_timestamp'), 
                                array("rev_page=$pg_id","rev_timestamp<=$dt"), 
                                __METHOD__, 
                                array('DISTINCT', 'ORDER BY'=>'rev_timestamp DESC', 'LIMIT'=>'1') 
                            );

        if( $xarow = $dbr->fetchObject( $xares ) ) {
            $memRevID = $xarow->rev_id;
            $memRevUnixTS = $xarow->rev_timestamp;
            $memRevTS = wfTimestamp( TS_RFC2822,  $memRevUnixTS );

            //$prev['uri'] = $alturi . "?title=" . $title . "&oldid=" . $prevRevID;
            $memuri = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$memRevID) );
            $mem['uri'] = $memuri;
            $mem['dt'] = $memRevTS;
        }

        // prev version
        $xares = $dbr->select( 
                                'revision', 
                                array('rev_id', 'rev_timestamp'), 
                                array("rev_page=$pg_id","rev_timestamp<$memRevUnixTS"), 
                                __METHOD__, 
                                array('DISTINCT', 'ORDER BY'=>'rev_timestamp DESC', 'LIMIT'=>'1') 
                            );

        if( $xarow = $dbr->fetchObject( $xares ) ) {
            $prevRevID = $xarow->rev_id;
            $prevRevUnixTS = $xarow->rev_timestamp;
            $prevRevTS = wfTimestamp( TS_RFC2822,  $prevRevUnixTS );

            //$prev['uri'] = $alturi . "?title=" . $title . "&oldid=" . $prevRevID;
            $prev['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$prevRevID) );
            $prev['dt'] = $prevRevTS;
        }

        // next version
        $xares = $dbr->select( 
                                'revision', 
                                array('rev_id', 'rev_timestamp'), 
                                array("rev_page=$pg_id","rev_timestamp>$memRevUnixTS"), 
                                __METHOD__, 
                                array('DISTINCT', 'ORDER BY'=> 'rev_timestamp ASC', 'LIMIT'=>'1') 
                            );

        if( $xarow = $dbr->fetchObject( $xares ) ) {
            $nextRevID = $xarow->rev_id;
            $nextRevUnixTS = $xarow->rev_timestamp;
            $nextRevTS = wfTimestamp( TS_RFC2822,  $nextRevUnixTS );

            //$next['uri'] = $alturi . "?title=" . $title . "&oldid=" . $nextRevID;
            $next['uri'] = wfAppendQuery( wfExpandUrl( $waddress ), array("title"=>$title, "oldid"=>$nextRevID) );
            $next['dt'] = $nextRevTS;
        }

        $header = array( 
                "Location" => $memuri,
                "Vary" => "negotiate, accept-datetime",
                "Link" => mmConstructLinkHeader( $first, $last, $mem, $next, $prev ) . $Link 
                );
        mmSend(302, $header, null);
        exit();
    }
}
