<?php
class TimeGate extends SpecialPage
{
    function TimeGate() {
        parent::__construct("TimeGate");
    }


    function execute( $par ) {

        global $wgRequest, $wgOut;
        global $wgMementoNamespace;
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

        if ( stripos( $requestURL, '?' ) ) {
            return;
        }

        // setting the default timezone to GMT. 
        date_default_timezone_set('GMT');

        /** URL of the server. It will be automatically built including https mode */
        $Server = $wgServer;

        $waddress = preg_replace( '/\$1/', '', $wgArticlePath );

        // getting the title of the page from the request uri
        $title = preg_replace( "|".$wgServer.$waddress."|", "", $par );

        // building the history uri in stages.
        $waddress = preg_replace( '/\/\$1/', '', $wgArticlePath );
        $historyuri = $Server . $waddress;

        // creating a db object to retrieve the old revision id from the db. 
        $dbr = wfGetDB( DB_SLAVE );
        $dbr->begin();

        // this section checks for namespaces in the title and 
        // picks up it's corresponding id to query the database...
        // the default value is 0... the "page" table will be checked assuming 
        // ":" is part of the title and not a namespace
        $new_title = $title;
        $namespace = "";
        $page_namespace_id = 0;

        if( stripos( $title, ':') ) {
            $ns_title = explode( ":", $title );
            $namespace = $ns_title[0];
            $new_title = $ns_title[1];
        }

        if( defined('NS_'.strtoupper($namespace)) && constant('NS_'.strtoupper($namespace)) )
            $page_namespace_id = constant( "NS_" . strtoupper($namespace) );


        $res_pg = $dbr->select( 'page', array('page_id'), array("page_title='$new_title'","page_namespace=$page_namespace_id"), __METHOD__, array('DISTINCT') );

        if ( !$res_pg ) {
            $msg = wfMsgForContent( 'timegate-404-namespace-title', $title );
            mmSend(404, array(), $msg);
            exit();
        }    

        $row_pg = $dbr->fetchObject( $res_pg );
        $pg_id = 0;
        if( $row_pg )
            $pg_id = $row_pg->page_id;

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
        // creating a db object to retrieve the old revision id from the db. 
        $dbr = wfGetDB( DB_SLAVE );
        $dbr->begin();

        $res_ar = $dbr->select( 'archive', array('ar_timestamp'), array("ar_title='$new_title'","ar_namespace=$page_namespace_id"), __METHOD__, array('ORDER BY'=>'ar_timestamp ASC', 'LIMIT'=>'1') );

        if( $dbr->fetchObject( $res_ar ) ) {  
            // checking if a revision exists for the requested date. 
            if (  
                $res_ar_ts = $dbr->select( 'archive', array('ar_timestamp'), array("ar_title='$new_title'","ar_namespace=$page_namespace_id","ar_timestamp <= $dt"), __METHOD__, array('ORDER BY'=>'ar_timestamp DESC', "LIMIT"=>"1") )
               ) { 
                $row_ar_ts = $dbr->fetchObject( $res_ar_ts );
                $ar_ts = $row_ar_ts->ar_timestamp;

                if ( $ar_ts ) {
                    // redirection is done to the "special page" for deleted articles. 
                    $historyuri .= "?title=Special:Undelete&target=".$title."&timestamp=".$ar_ts;
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

        // if the datetime input is not valid, the default is 1970, which will be omitted.
        $raw_dt = $wgRequest->getHeader("ACCEPT-DATETIME");

        // looks for datetime enclosed in ""
        $raw_dt = preg_replace( '/"/', '', $raw_dt ); 
        $raw_dt = preg_replace( '/"/', '', $raw_dt ); 

        // looking for time interval
        $int_from = '';
        $int_to = '';
        $interval = false;

        $dt_arr = explode( ';', $raw_dt );
        if( count($dt_arr) == 3 ) {
            $req_dt = $dt_arr[0];
            $int_from = preg_replace( '/-/', '', $dt_arr[1] );
            $int_to = preg_replace( '/\+/', '', $dt_arr[2] );
            $interval = true;
        }
        elseif( count($dt_arr) > 1 ) {
            $msg = wfMsgForContent( 'timegate-400-interval', $raw_dt );

            $msg .= wfMsgForContent( 'timegate-400-first-memento', $first['uri'] );
            $msg .= wfMsgForContent( 'timegate-400-last-memento', $last['uri'] );

            $header = array( "Link" => mmConstructLinkHeader( $first, $last ) . $Link );
            mmSend(400, $header, $msg);
            exit();
        }
        else {
            $req_dt = $dt_arr[0];
        }


        // validating date time...
        $dt = strtotime( $req_dt );
        $dt = date( 'YmdHis', $dt );
        $wgMementoReqDateTime = $dt;

        if( $dt == 19700101000000 ) {
            $msg = wfMsgForContent( 'timegate-400-date', $req_dt );

            $msg .= wfMsgForContent( 'timegate-400-first-memento', $first['uri'] );
            $msg .= wfMsgForContent( 'timegate-400-last-memento', $last['uri'] );

            $header = array( "Link" => mmConstructLinkHeader( $first, $last ) . $Link );
            mmSend(400, $header, $msg);
            exit();
        }

        $from = '';
        $to = '';
        if( $int_from != NULL && $int_to != NULL ) {
            //validating interval & duration

            if( !function_exists('date_sub') && !function_exists('date_add') ) { 
                $msg = wfMsg( 'timegate-501-interval' );
                mmSend(501, array(), $msg);
                exit();
            }

            try {
                $t_from = new DateTime( $req_dt );
                $t_from->sub( new DateInterval( $int_from ) );

                $t_to = new DateTime( $req_dt );
                $t_to->add( new DateInterval( $int_to ) );

            } catch( Exception $e ) {

                $msg = wfMsgForContent( 'timegate-400-interval', $raw_dt, $int_from, $int_to );

                $msg .= wfMsgForContent( 'timegate-400-first-memento', $first['uri'] );
                $msg .= wfMsgForContent( 'timegate-400-last-memento', $last['uri'] );

                $header = array( "Link" => mmConstructLinkHeader( $first, $last ) . $Link );
                mmSend(400, $header, $msg);
                exit();
            } 

            $from = $t_from->format( 'YmdHis' );
            $to = $t_to->format( 'YmdHis' );
        }

        return array( $dt, $interval, $from, $to, $raw_dt ); 
    }




    function tgGetMementoForResource($pg_id, $historyuri, $title) {

        global $wgRequest;

        // creating a db object to retrieve the old revision id from the db. 
        $dbr = wfGetDB( DB_SLAVE );
        $dbr->begin();

        $alt_header = '';
        $last = array(); $first = array(); $next = array(); $prev = array(); $mem = array();

        $alturi = $historyuri;
        //$historyuri .= "/";

        // querying the database and building info for the alternates header. 
        $xares = $dbr->select( 'revision', array('rev_id, rev_timestamp'), array("rev_page='$pg_id'"), __METHOD__, array('DISTINCT',"ORDER BY"=>"rev_id DESC") );
        while( $xarow = $dbr->fetchObject( $xares ) ) {
            $revTS[] = $xarow->rev_timestamp;
            $revID[] = $xarow->rev_id;
        }

        $cnt = count($revTS);

        // the most recent version's timestamp and id.
        $recentRevID = $revID[0];
        $recentRevTS = $revTS[0];
        $recentRevTS = mmConvertTimestamp( $recentRevTS );
        $last['uri'] = $alturi . "?title=" . $title . "&oldid=" . $recentRevID;
        $last['dt'] = $recentRevTS;

        // the oldest version's timestamp and id.
        $oldestRevID = $revID[$cnt-1];
        $oldestRevTS = $revTS[$cnt-1];
        $oldestRevTS = mmConvertTimestamp( $oldestRevTS );
        $first['uri'] = $alturi . "?title=" . $title . "&oldid=" . $oldestRevID;
        $first['dt'] = $oldestRevTS;

        $Link = "<" . $alturi . "/". $title .">; rel=\"original latest-version\", ";
        $Link .= "<" . $alturi."/Special:TimeMap/".$alturi ."/". $title .">; rel=\"timemap\"; type=\"application/link-format\"";

        $current = date( 'YmdHis', time() );


        // checking for the occurance of the accept datetime header.
        if( !$wgRequest->getHeader('ACCEPT-DATETIME') ) {

            $mem['uri']= $alturi . "?title=" . $title . "&oldid=" . $recentRevID;
            $mem['dt'] = $recentRevTS;

            if( $revID[1] ) {
                $prevRevID = $revID[1];
                $prevRevTS = $revTS[1];
                $prevRevTS = mmConvertTimestamp( $prevRevTS );
                $prev['uri']= $alturi . "?title=" . $title . "&oldid=" . $prevRevID;
                $prev['dt'] = $prevRevTS;
            }
            $header = array( 
                    "Location" => $alturi . "?title=" . $title . "&oldid=" . $recentRevID,
                    "Vary" => "negotiate, accept-datetime",
                    "Link" => mmConstructLinkHeader( $first, $last, $mem, '', $prev ) . $Link 
                    );

            mmSend(302, $header, null);
            exit();
        }

        list($dt, $interval, $from, $to, $raw_dt) = $this->tgParseRequestDateTime($first, $last, $Link);

        // if the requested time is earlier than the first memento, the first memento will be returned
        //if the requested time is past the last memento, or in the future, the last memento will be returned. 
        if( !$interval ) {
            if( $dt < $revTS[$cnt-1] ) {
                $dt = $revTS[$cnt-1];
            }
            elseif( $dt > $revTS[0] ) {
                $dt = $revTS[0];
            }
        }
        elseif( $dt < $revTS[$cnt-1] ) {
            $memuri = $historyuri . "?title=".$title."&oldid=".$revID[$cnt-1];
            $mem['uri'] = $alturi . "?title=" . $title . "&oldid=" . $revID[$cnt-1];
            $mem['dt'] = mmConvertTimestamp( $revTS[$cnt-1] );

            $msg = wfMsgForContent( 'timegate-406-interval', $raw_dt ); 

            $header = array( "Link" => mmConstructLinkHeader( $first, $last, $mem ) . $Link );
            mmSend(406, $header, $msg);
            exit();
        }
        elseif( $dt >  $revTS[0] ) {
            $memuri = $historyuri . "?title=".$title."&oldid=".$revID[0];
            $mem['uri'] = $alturi . "?title=" . $title . "&oldid=" . $revID[0];
            $mem['dt'] = mmConvertTimestamp( $revTS[0] );

            $msg = wfMsgForContent( 'timegate-406-interval', $raw_dt ); 

            $header = array( "Link" => mmConstructLinkHeader( $first, $last, $mem ) . $Link );
            mmSend(406, $header, $msg);
            exit();
        }

        for( $i=0; $i<$cnt; $i++ ) 
            if( $revTS[$i] <= $dt )
                break;

        // memento found!
        $memuri = $historyuri . "?title=".$title."&oldid=".$revID[$i];
        $mem['uri'] = $alturi . "?title=" . $title . "&oldid=" . $revID[$i];
        $mem['dt'] = mmConvertTimestamp( $revTS[$i] );

        // previsous version's timestamp and id. 
        if( $revTS[$i+1] ) {
            $prevRevID = $revID[$i+1];
            $prevRevTS = $revTS[$i+1]; // The timestamps are arranged in descending order!

            $prevRevTS = mmConvertTimestamp( $prevRevTS );
            $prev['uri'] = $alturi . "?title=" . $title . "&oldid=" . $prevRevID;
            $prev['dt'] = $prevRevTS;
        }
        // next version's timestamp and id.
        if( $revTS[$i-1] ) {
            $nextRevID = $revID[$i-1];
            $nextRevTS = $revTS[$i-1];

            $nextRevTS = mmConvertTimestamp( $nextRevTS );
            $next['uri'] = $alturi . "?title=" . $title . "&oldid=" . $nextRevID;
            $next['dt'] = $nextRevTS;

        }

        if( $interval ) {
            if( $from <= $revTS[$i] ) {
                $header = array( 
                        "Location" => $memuri,
                        "Vary" => "negotiate, accept-datetime",
                        "Link" => mmConstructLinkHeader( $first, $last, $mem, $next, $prev ) . $Link 
                        );
                mmSend(302, $header, null);
                exit();
            }
            elseif( $from > $revTS[$i] && $to >= $revTS[$i-1] ) {
                if( $i-2 >= 0 ) {
                    $nnextRevTS = $revTS[$i-2];
                    $nnextRevID = $revID[$i-2];
                    $nnextRevTS = mmConvertTimestamp( $nnextRevTS );

                    $nnext['uri'] = $alturi . "?title=" . $title . "&oldid=" . $nnextRevID;
                    $nnext['dt'] = $nnextRevTS;
                }
                else {
                    $nnext = $last;
                }

                $memuri = $next['uri'];

                $header = array( 
                        "Location" => $memuri, 
                        "Vary" => "negotiate, accept-datetime",
                        "Link" => mmConstructLinkHeader( $first, $last, $next, $nnext, $prev ) . $Link 
                        );
                mmSend(302, $header, null);
                exit();
            }
            else {
                $msg = wfMsgForContent( 'timegate-406-interval', $raw_dt ); 

                $header = array( "Link" => mmConstructLinkHeader( $first, $last, $mem ) . $Link );
                mmSend(406, $header, $msg);
                exit();
            }
        }
        else {
            $header = array( 
                    "Location" => $memuri,
                    "Vary" => "negotiate, accept-datetime",
                    "Link" => mmConstructLinkHeader( $first, $last, $mem, $next, $prev ) . $Link 
                    );
            mmSend(302, $header, null);
            exit();
        }
    }
}
