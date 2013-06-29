<?php

/*
 * Given the string $linkvalues, which is the value part of the Link: header,
 * return an array of key-value pairs for easier access.
 */
function extractItemsFromLink($linkvalues) {

#    echo "\r\n";
#    echo "=$linkvalues\r\n";

    $relations = array();

    $datetime = NULL;
    $item = NULL;

    # TODO: create a better Link splitter, this doesn't split correctly
    # but is good enough for current testing on the Mediawiki Memento Plugin
    $items = preg_split('/", /', $linkvalues);  

    foreach ($items as $item) {
        
#        echo "\r\n";
#        echo "+$item\r\n";

        # TODO: switch to explode
        $data = preg_split('/;/', $item);

        foreach ($data as $datum) {

            #echo "-$datum\r\n";

            $datum = trim($datum);

            if ($datum[0] == '<') {
                $url = preg_replace("/<([^>]*)>.*/", "$1", $datum);
            }

            if (substr($datum, 0, 3) == "rel") {
                $rel = substr($datum, 3);
                list($kw, $rel) = preg_split('/=/', $rel);
                $rel = str_replace('"', '', $rel);
            }

            if (substr($datum, 0, 8) == "datetime") {
                $datetime = substr($datum, 8);
                list($kw, $datetime) = preg_split('/=/', $datetime);
                $datetime = str_replace('"', '', $datetime);
            }

            #echo "datetime = $datetime\r\n";

        }

#        echo "++setting relations with datetime = $datetime\r\n";
        $relations[$rel] = array();
        $relations[$rel]["url"] = $url;
        $relations[$rel]["datetime"] = $datetime;
        $datetime = NULL;

    }

#    echo "\r\n";
#    echo "FINAL\r\n";
#    echo "\r\n";
#
#    foreach ($relations as $key => $value) {
#
#        echo "relations['$key'] = ". $relations[$key] . "\r\n";
#        echo "relations['$key']['url'] = " . $relations[$key]['url'] . "\r\n";
#
#        if (array_key_exists('datetime', $relations[$key])) {
#            echo "relations['$key']['datetime'] = " . $relations[$key]['datetime'] . "\r\n";
#        }
#
#    }

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
