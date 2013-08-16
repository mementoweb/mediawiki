<?php

require_once(getenv('TESTDATADIR') . '/authentication-data.php');

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

/*
 * Get the cookies set in the response
 *
 */
function extractCookiesSetInResponse($response) {

	$lines = preg_split("/\r\n/", $response);

	$cookies = array();

	foreach ($lines as $line) {

		if (strlen($line) == 0) {
			break;
		}

		if (strpos($line, ":") !== false) {
			list($header, $value) = preg_split("/: /", $line, 2);
			if ($header == "Set-Cookie") {
				$cvalues = preg_split('/;/', $value);
				list($cname, $cvalue) = preg_split("/=/", $cvalues[0]);
				$cookies[$cname] = $cvalue;
			}
		}

	}

	return $cookies;
}

/*
 * Authenticate!!!
 */
function authenticateWithMediawiki() {
		global $mwLoginFormUrl;
		global $mwLoginActionUrl;
		global $wpName;
		global $wpPassword;
		global $wpRemember;
		global $wpLoginAttempt;
		global $wpLoginToken;
		global $sessionCookieString;
		global $mwDbName;

		global $HOST;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -k -i --url '$mwLoginFormUrl'`;

        $headers = extractHeadersFromResponse($response);
        $statusline = extractStatuslineFromResponse($response);
		$entity = extractEntityFromResponse($response);
		$cookies = extractCookiesSetInResponse($response);

		$matches = array();

		$pattern = '/\<input type="hidden" name="wpLoginToken" value="([^"]*)" \/\>/';
		preg_match( $pattern, $entity, $matches );

		$wpLoginToken = $matches[1];

		$requestEntity = "wpName=$wpName&wpPassword=$wpPassword&wpLoginAttempt=Log+in&wpLoginToken=$wpLoginToken";

		$cookie_session = $cookies["${mwDbName}_session"];

		$cookies = "${mwDbName}_session=$cookie_session";

		$response = `curl -s -i -X POST -d '$requestEntity' -H 'Content-Type: application/x-www-form-urlencoded' -b '$cookies' --url '$mwLoginActionUrl'`;

		$statusline = extractStatuslineFromResponse($response);

		if ($statusline['code'] != "302") {
			echo 'TESTUSERNAME = [' . getenv('TESTUSERNAME') . "]\n";
			echo 'TESTPASSWORD = [' . getenv('TESTPASSWORD') . "]\n";
			echo 'wpName = [' . $wpName . "]\n";
			echo 'wpPassword = [' . $wpPassword . "]\n";
			trigger_error("Authentication failed, check that the TESTUSERNAME and TESTPASSWORD environment variables are set correctly.", E_USER_ERROR);
		}

		$cookies = extractCookiesSetInResponse($response);

		$cookieUserID = $cookies["${mwDbName}UserID"];
		$cookieUserName = $cookies["${mwDbName}UserName"];
		$cookieToken = $cookies["${mwDbName}Token"];
		$cookie_session = $cookies["${mwDbName}_session"];

		$sessionCookieString = "${mwDbName}_session=$cookie_session; ${mwDbName}UserID=$cookieUserID; ${mwDbName}UserName=$cookieUserName";

		return $sessionCookieString;
}

/*
 * Log out!!!
 */

function logOutOfMediawiki() {
		global $sessionCookieString;
		global $mwLogoutActionUrl;

		$uagent = "Memento-Mediawiki-Plugin/Test";

		$response = `curl -s -e '$uagent' -b '$sessionCookieString' -k -i --url '$mwLogoutActionUrl'`;
}

/*
 * only get the headers as a string, no processing
 */
function extractHeadersStringFromResponse($response) {

    $lines = preg_split("/\r\n/", $response);

	$headers = '';
    
    foreach ($lines as $line) {
    
        if (strlen($line) == 0) {
            break;
        }

		$headers .= $line . "\r\n";
    
    }

    return $headers;
}

?>
