<?php
class TimeMap extends SpecialPage
{
    function TimeMap() {
        parent::__construct("TimeMap");
    }

    function execute( $par ) {

        global $wgArticlePath;
        global $wgServer;
        global $wgRequest;

        $requestURL = $wgRequest->getRequestURL();
        $this->setHeaders();

        wfLoadExtensionMessages( 'TimeMap' );

        if( !$par ) {
            return;
        }

        // setting the default timezone to GMT. 
        date_default_timezone_set('GMT');

        /** URL of the server. It will be automatically built including https mode */
        $Server = $wgServer;

        // getting the title of the page from the request uri
        $waddress = preg_replace( '/\$1/', '', $wgArticlePath );

        $title = preg_replace( "|".$wgServer.$waddress."|", "", $par );

        // building the history uri in stages.
        $waddress = preg_replace( '/\/\$1/', '', $wgArticlePath );
        $historyuri = $Server . $waddress;

        #if( substr( $historyuri, -1 ) != '/' )
        #    $historyuri .= '/';

        // creating a db object to retrieve the old revision id from the db. 
        $dbr = wfGetDB( DB_SLAVE );
        $dbr->begin();

        // this section checks for namespaces in the title and 
        // picks up it's corresponding id to query the database...

        // the default value is 0... the "page" table will be checked assuming 
        // ":" is part of the title and not a namespace
        $new_title = $title;

        if( stripos( $title, ':') ) {
            $ns_title = explode( ":", $title );
            $namespace = $ns_title[0];
            $new_title = $ns_title[1];
        }

        if( defined('NS_'.strtoupper($namespace)) && constant('NS_'.strtoupper($namespace)) )
            $page_namespace_id = constant( "NS_" . strtoupper($namespace) );

        if( !$page_namespace_id ) 
            $page_namespace_id = 0;

        $res_pg = $dbr->select( "page", array('page_id'), array("page_title='$new_title'","page_namespace=$page_namespace_id"), __METHOD__, array('DISTINCT') ); 
        if ( $res_pg ) {
            $row_pg = $dbr->fetchObject( $res_pg );
            $pg_id = $row_pg->page_id;

            if( $pg_id > 0 ) {
                $wikiaddr = explode( "Special:TimeMap/", $requestURL );

                // querying the database and building info for the link header. 
                $xares = $dbr->select( "revision", array('rev_id', 'rev_timestamp'), array("rev_page=$pg_id"), __METHOD__, array("ORDER BY"=>"rev_id DESC") );
                while( $xarow = $dbr->fetchObject( $xares ) ) {
                    $revTS[] = $xarow->rev_timestamp;
                    $revID[] = $xarow->rev_id;
                }

                $cnt = count($revTS);
                $requri = $Server . $requestURL;

                $timegate = preg_replace( '/Special:TimeMap/', 'Special:TimeGate', $requri );

                $header = array( 
                        "Content-Type" => "application/link-format;charset=UTF-8",
                        "Link" => "<".$requri.">; anchor=\"".$wikiaddr[1]."\"; rel=\"timemap\"; type=\"application/link-format\"" );

                mmSend(200, $header, null);

                echo "<" . $timegate . ">;rel=\"timegate\", \n";
                echo "<" . $requri . ">;rel=\"timemap\", \n";

                echo "<" . $wikiaddr[1] . ">;rel=\"original latest-version\", \n";
                $uri = $historyuri . "?title=".$title."&oldid=".$revID[$cnt-1];
                echo "<" . $uri . ">;rel=\"first memento\";datetime=\"" . mmConvertTimestamp( $revTS[$cnt-1] ). "\", \n";

                if( $cnt > 2 ) {
                    for($i=$cnt-2; $i>0; $i-- ) {
                        $uri = $historyuri . "?title=".$title."&oldid=".$revID[$i];            
                        echo "<" . $uri . ">;rel=\"memento\";datetime=\"" . mmConvertTimestamp( $revTS[$i] ). "\", \n";
                    }
                }
                else {
                    $uri = $historyuri . "?title=".$title."&oldid=".$revID[0];
                    echo "<" . $uri . ">;rel=\"memento\";datetime=\"" . mmConvertTimestamp( $revTS[0] ). "\", \n";
                }

                $uri = $historyuri . "?title=".$title."&oldid=".$revID[0];
                echo "<" . $uri . ">;rel=\"last memento\";datetime=\"" . mmConvertTimestamp( $revTS[0] ). "\"";
                exit();
            }
            else {
                $msg = wfMsgForContent( 'timemap-404-title', $title );
                mmSend(404, null, $msg);
                exit();
            }
        }
        else {
            $msg = wfMsgForContent( 'timemap-404-namespace-title', $title );
            mmSend(404, null, $msg);
            exit();
        }
    } 
}
