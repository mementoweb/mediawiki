<?php
/*
 * Given a $host and a $port, sends the $request and returns
 * a string containing the complete $response.
 *
 * NOTE:  Does not work with HTTP Pipelining!
 */
function HTTPFetch($host, $port, $request) {

    $fp = fsockopen('localhost', $port, $errno, $errstr, 30);
    
    if (!$fp) {
        $this->assertTrue(FALSE);
    } else {
    
        fwrite($fp, $request);
    
        $response = "";
    
        while (!feof($fp)) {
             $response .= fgets($fp);
        }
    
        fclose($fp);
    }

    return $response;
}

/* 
 * Given an HTTP $response, extracts the headers into a more easy-to-use
 * key,value pair stored in an array.
 *
 */
function extractHeadersFromResponse($response) {

    $lines = preg_split("/\r\n/", $response);
    
    $headers = array();
    
    foreach ($lines as $line) {
    
        if (strlen($line) == 0) {
            break;
        }
    
        if (strpos($line, "HTTP") !== false) {
            list($version, $code, $message) = preg_split("/ /", $line);
        } else {
            if (strpos($line, ":") !== false) {
                list($header, $value) = preg_split("/: /", $line, 2);
                $headers[$header] = $value;
            }
        }
    }

    return $headers;
}

/*
 * Given an HTTP $response, extracts the status line into a more easy-to-use
 * key, value pair stored in an array.
 */
function extractStatuslineFromResponse($response) {
    $lines = preg_split("/\r\n/", $response);

    $statusline = array();

    foreach ($lines as $line) {
    
        if (strlen($line) == 0) {
            break;
        }
    
        if (strpos($line, "HTTP") !== false) {
            list($version, $code, $message) = preg_split("/ /", $line);
            break;
        }
    }

    $statusline["version"] = $version;
    $statusline["code"] = $code;
    $statusline["message"] = $message;

    return $statusline;
}

/*
 * Given an HTTP $response, extracts the entity from the response as $entity.
 *
 */
function extractEntityFromResponse($response) {
	$entity = NULL;
	$entityStart = strpos($response, "\r\n\r\n") + 4;
	$entity = substr($response, $entityStart);
	return $entity;
}

?>
