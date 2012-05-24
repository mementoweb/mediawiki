<?php
    /**
    * Internationalisation file for extension MyExtension.
    *
    * @addtogroup Extensions
    */

    $messages = array();

    $messages['en'] = array(
        'timegate' => 'Memento TimeGate',
        'timegate-desc' => 'This is a Memento TimeGate.',
        'timegate-welcome-message' => 'This is a Memento Timegate for your wiki. To see Memento in action, either follow the instructions from the <a href="http://www.mediawiki.org/wiki/Extension:Memento">MediaWiki Extension</a> page or type in the address of the wiki page in this format: <a href=#>http://yourwikisite/wiki/index.php/Special:TimeGate/http://yourwikisite/wiki/index.php/Main_Page</a> where, the address that follows the TimeGate URL is the address of the article.<br/>',
        'timegate-404-namespace-title' => "Error 404: Either the resource does not exist or the namespace is not understood by memento for the title: '$1'.",
        'timegate-404-title' => "Error 404: Resource does not exist for the title: '$1'.",
        'timegate-400-interval' => "Error 400: Requested date $1 not parseable. Did not understand duration/interval.<br/>",
        'timegate-400-date' => "Error 400: Requested date $1 not parseable.<br/>",
        'timegate-400-first-memento' => "<b>First Memento:</b> <a href=$1>$1</a><br/>",
        'timegate-400-last-memento' => "<b>Last Memento:</b> <a href=$1>$1</a><br/>",
        'timegate-406-interval' => "Error 406: No Memento was found for the requested duration $1.<br/>",
        'timegate-501-interval' => "Error 501: Time Interval is not implemented. PHP Version > 5.3 is required.<br/>"

    );
