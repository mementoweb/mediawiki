<?php
/*

	Mediawiki's authentication pattern:

	1. Generate wpLoginToken on the login form
	2. POST the following fields in cleartext to the form action URL:
		wpName
		wpPassword
		wpLoginAttempt
		wpLoginToken - generated for the login form
	3. Mediawiki responds with a 302 containing the following Set-Cookie headers, like so:
		Set-Cookie: mediawiki_1_21_1UserID=1; expires=Tue, 04-Feb-2014 01:40:39 GMT; path=/; httponly
		Set-Cookie: mediawiki_1_21_1UserName=Root; expires=Tue, 04-Feb-2014 01:40:39 GMT; path=/; httponly
		Set-Cookie: mediawiki_1_21_1_session=3b1e27eebc1608071011020288d45eff; path=/; HttpOnly
	4. The Cookie: header is used for each subsequent request like so:
		mediawiki_1_21_1LoggedOut=20130808013939; mediawiki_1_21_1UserID=1; mediawiki_1_21_1UserName=Root; mediawiki_1_21_1_session=3b1e27eebc1608071011020288d45eff
		
		

*/

// User-configurable session
$mwLoginFormUrl = "http://localhost/~smj/mediawiki-1.21.1/index.php?title=Special:UserLogin";
$mwLoginActionUrl = "http://localhost/~smj/mediawiki-1.21.1/index.php?title=Special:UserLogin&action=submitlogin&type=login";
$mwLogoutActionUrl = "http://localhost/~smj/mediawiki-1.21.1/index.php?title=Special:UserLogout";
$wpName = "Root";
$wpPassword = "abcd1234";
$mwDbName = "mediawiki_1_21_1";

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
