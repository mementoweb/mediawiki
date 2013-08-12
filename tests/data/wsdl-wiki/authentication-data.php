<?php
/*
	This file contains the authentication information and other global
	variables to be used with your mediawiki instance.
*/

// User-configurable session
$mwDbName = "mediawiki_1_21_1";

$mwLoginFormUrl = "https://ws-dl.cs.odu.edu/wiki/index.php?title=Special:UserLogin";
$mwLoginActionUrl = "https://ws-dl.cs.odu.edu/wiki/index.php?title=Special:UserLogin&action=submitlogin&type=login";
$mwLogoutActionUrl = "http://ws-dl.cs.odu.edu/wiki/index.php?title=Special:UserLogout";
$wpName = urlencode($_ENV['TESTUSERNAME']);
$wpPassword = urlencode($_ENV['TESTPASSWORD']);
$mwDbName = "wikidb";


// globals for use with authentication
$wpRemember = "1";
$wpLoginAttempt = "Log in";
$wpLoginToken = "";
$cookieUserID = "";
$cookieUserName = "";
$cookieToken = "";
$cookie_session = "";
$sessionCookieString = "";
?>
