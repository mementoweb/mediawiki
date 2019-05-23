<?php

error_reporting( E_ALL | E_NOTICE | E_STRICT );

class SimpleTest extends PHPUnit\Framework\TestCase {

	public static $instance = 0;

	/**
	 * @test
	 * @group simple
	 *
	 */
	public function SimpleLittleTests() {
		echo "executing simple little test\n";
		$this->assertEquals("hi", "hi");

		$expected = "%AError 400: Requested date '%s' not parseable.<br /><b>First Memento:</b> %s<br /><b>Last Memento:</b> %s<br />%A";
		$entity_whole =<<<EOD
<!DOCTYPE html>
<html class="client-nojs" lang="en" dir="ltr">
<head>
<meta charset="UTF-8"/>
<title>Memento TimeGate - test wiki</title>
<script>document.documentElement.className = document.documentElement.className.replace( /(^|\s)client-nojs(\s|$)/, "$1client-js$2" );</script>
<script>(window.RLQ=window.RLQ||[]).push(function(){mw.config.set({"wgCanonicalNamespace":"Special","wgCanonicalSpecialPageName":"TimeGate","wgNamespaceNumber":-1,"wgPageName":"Special:TimeGate/Kevan_Lannister","wgTitle":"TimeGate/Kevan Lannister","wgCurRevisionId":0,"wgRevisionId":0,"wgArticleId":0,"wgIsArticle":false,"wgIsRedirect":false,"wgAction":"view","wgUserName":null,"wgUserGroups":["*"],"wgCategories":[],"wgBreakFrames":true,"wgPageContentLanguage":"en","wgPageContentModel":"wikitext","wgSeparatorTransformTable":["",""],"wgDigitTransformTable":["",""],"wgDefaultDateFormat":"dmy","wgMonthNames":["","January","February","March","April","May","June","July","August","September","October","November","December"],"wgMonthNamesShort":["","Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],"wgRelevantPageName":"Special:TimeGate/Kevan_Lannister","wgRelevantArticleId":0,"wgRequestId":"021ef97374808c816827c37e","wgCSPNonce":false,"wgIsProbablyEditable":false,"wgRelevantPageIsProbablyEditable":false});mw.loader.state({"site.styles":"ready","noscript":"ready","user.styles":"ready","user":"ready","user.options":"ready","user.tokens":"loading","mediawiki.legacy.shared":"ready","mediawiki.legacy.commonPrint":"ready","mediawiki.skinning.interface":"ready","skins.vector.styles":"ready"});mw.loader.implement("user.tokens@0tffind",function($,jQuery,require,module){/*@nomin*/mw.user.tokens.set({"editToken":"+\\","patrolToken":"+\\","watchToken":"+\\","csrfToken":"+\\"});
});RLPAGEMODULES=["site","mediawiki.page.startup","mediawiki.user","mediawiki.page.ready","mediawiki.searchSuggest","skins.vector.js"];mw.loader.load(RLPAGEMODULES);});</script>
<link rel="stylesheet" href="/load.php?debug=false&amp;lang=en&amp;modules=mediawiki.legacy.commonPrint%2Cshared%7Cmediawiki.skinning.interface%7Cskins.vector.styles&amp;only=styles&amp;skin=vector"/>
<script async="" src="/load.php?debug=false&amp;lang=en&amp;modules=startup&amp;only=scripts&amp;skin=vector"></script>
<meta name="ResourceLoaderDynamicStyles" content=""/>
<meta name="generator" content="MediaWiki 1.32.0"/>
<meta name="robots" content="noindex,nofollow"/>
<link rel="shortcut icon" href="/favicon.ico"/>
<link rel="search" type="application/opensearchdescription+xml" href="/opensearch_desc.php" title="test wiki (en)"/>
<link rel="EditURI" type="application/rsd+xml" href="http://localhost:4455/api.php?action=rsd"/>
<link rel="alternate" type="application/atom+xml" title="test wiki Atom feed" href="/index.php?title=Special:RecentChanges&amp;feed=atom"/>
<!--[if lt IE 9]><script src="/load.php?debug=false&amp;lang=en&amp;modules=html5shiv&amp;only=scripts&amp;skin=vector&amp;sync=1"></script><![endif]-->
</head>
<body class="mediawiki ltr sitedir-ltr mw-hide-empty-elt ns--1 ns-special mw-special-TimeGate page-Special_TimeGate_Kevan_Lannister rootpage-Special_TimeGate_Kevan_Lannister skin-vector action-view">		<div id="mw-page-base" class="noprint"></div>
		<div id="mw-head-base" class="noprint"></div>
		<div id="content" class="mw-body" role="main">
			<a id="top"></a>
			<div class="mw-indicators mw-body-content">
</div>
<h1 id="firstHeading" class="firstHeading" lang="en">Memento TimeGate</h1>			<div id="bodyContent" class="mw-body-content">
								<div id="contentSub"></div>
				<div id="jump-to-nav"></div>				<a class="mw-jump-link" href="#mw-head">Jump to navigation</a>
				<a class="mw-jump-link" href="#p-search">Jump to search</a>
				<div id="mw-content-text">
				<p>Error 400: Requested date 'bad-input' not parseable.<br/><b>First Memento:</b> <a rel="nofollow" class="external free" href="http://localhost:4455/index.php?title=Kevan_Lannister&amp;oldid=2">http://localhost:4455/index.php?title=Kevan_Lannister&amp;oldid=2</a><br/><b>Last Memento:</b> <a rel="nofollow" class="external free" href="http://localhost:4455/index.php?title=Kevan_Lannister&amp;oldid=127">http://localhost:4455/index.php?title=Kevan_Lannister&amp;oldid=127</a><br/>
</p><p id="mw-returnto">Return to <a href="/index.php/Main_Page" title="Main Page">Main Page</a>.</p>
</div>					<div class="printfooter">
						Retrieved from "<a dir="ltr" href="http://localhost:4455/index.php/Special:TimeGate/Kevan_Lannister">http://localhost:4455/index.php/Special:TimeGate/Kevan_Lannister</a>"					</div>
				<div id="catlinks" class="catlinks catlinks-allhidden" data-mw="interface"></div>				<div class="visualClear"></div>
							</div>
		</div>
		<div id="mw-navigation">
			<h2>Navigation menu</h2>
			<div id="mw-head">
									<div id="p-personal" role="navigation" class="" aria-labelledby="p-personal-label">
						<h3 id="p-personal-label">Personal tools</h3>
						<ul>
							<li id="pt-anonuserpage">Not logged in</li><li id="pt-anontalk"><a href="/index.php/Special:MyTalk" title="Discussion about edits from this IP address [n]" accesskey="n">Talk</a></li><li id="pt-anoncontribs"><a href="/index.php/Special:MyContributions" title="A list of edits made from this IP address [y]" accesskey="y">Contributions</a></li><li id="pt-createaccount"><a href="/index.php?title=Special:CreateAccount&amp;returnto=Special%3ATimeGate%2FKevan+Lannister" title="You are encouraged to create an account and log in; however, it is not mandatory">Create account</a></li><li id="pt-login"><a href="/index.php?title=Special:UserLogin&amp;returnto=Special%3ATimeGate%2FKevan+Lannister" title="You are encouraged to log in; however, it is not mandatory [o]" accesskey="o">Log in</a></li>						</ul>
					</div>
									<div id="left-navigation">
										<div id="p-namespaces" role="navigation" class="vectorTabs" aria-labelledby="p-namespaces-label">
						<h3 id="p-namespaces-label">Namespaces</h3>
						<ul>
							<li id="ca-nstab-special" class="selected"><span><a href="/index.php/Special:TimeGate/Kevan_Lannister" title="This is a special page, and it cannot be edited">Special page</a></span></li>						</ul>
					</div>
										<div id="p-variants" role="navigation" class="vectorMenu emptyPortlet" aria-labelledby="p-variants-label">
												<input type="checkbox" class="vectorMenuCheckbox" aria-labelledby="p-variants-label" />
						<h3 id="p-variants-label">
							<span>Variants</span>
						</h3>
						<div class="menu">
							<ul>
															</ul>
						</div>
					</div>
									</div>
				<div id="right-navigation">
										<div id="p-views" role="navigation" class="vectorTabs emptyPortlet" aria-labelledby="p-views-label">
						<h3 id="p-views-label">Views</h3>
						<ul>
													</ul>
					</div>
										<div id="p-cactions" role="navigation" class="vectorMenu emptyPortlet" aria-labelledby="p-cactions-label">
						<input type="checkbox" class="vectorMenuCheckbox" aria-labelledby="p-cactions-label" />
						<h3 id="p-cactions-label"><span>More</span></h3>
						<div class="menu">
							<ul>
															</ul>
						</div>
					</div>
										<div id="p-search" role="search">
						<h3>
							<label for="searchInput">Search</label>
						</h3>
						<form action="/index.php" id="searchform">
							<div id="simpleSearch">
								<input type="search" name="search" placeholder="Search test wiki" title="Search test wiki [f]" accesskey="f" id="searchInput"/><input type="hidden" value="Special:Search" name="title"/><input type="submit" name="fulltext" value="Search" title="Search the pages for this text" id="mw-searchButton" class="searchButton mw-fallbackSearchButton"/><input type="submit" name="go" value="Go" title="Go to a page with this exact name if it exists" id="searchButton" class="searchButton"/>							</div>
						</form>
					</div>
									</div>
			</div>
			<div id="mw-panel">
				<div id="p-logo" role="banner"><a class="mw-wiki-logo" href="/index.php/Main_Page"  title="Visit the main page"></a></div>
						<div class="portal" role="navigation" id="p-navigation" aria-labelledby="p-navigation-label">
			<h3 id="p-navigation-label">Navigation</h3>
			<div class="body">
								<ul>
					<li id="n-mainpage-description"><a href="/index.php/Main_Page" title="Visit the main page [z]" accesskey="z">Main page</a></li><li id="n-recentchanges"><a href="/index.php/Special:RecentChanges" title="A list of recent changes in the wiki [r]" accesskey="r">Recent changes</a></li><li id="n-randompage"><a href="/index.php/Special:Random" title="Load a random page [x]" accesskey="x">Random page</a></li><li id="n-help-mediawiki"><a href="https://www.mediawiki.org/wiki/Special:MyLanguage/Help:Contents">Help about MediaWiki</a></li>				</ul>
							</div>
		</div>
			<div class="portal" role="navigation" id="p-tb" aria-labelledby="p-tb-label">
			<h3 id="p-tb-label">Tools</h3>
			<div class="body">
								<ul>
					<li id="t-specialpages"><a href="/index.php/Special:SpecialPages" title="A list of all special pages [q]" accesskey="q">Special pages</a></li><li id="t-print"><a href="/index.php?title=Special:TimeGate/Kevan_Lannister&amp;printable=yes" rel="alternate" title="Printable version of this page [p]" accesskey="p">Printable version</a></li>				</ul>
							</div>
		</div>
				</div>
		</div>
				<div id="footer" role="contentinfo">
						<ul id="footer-places">
								<li id="footer-places-privacy"><a href="/index.php/Test_wiki:Privacy_policy" title="Test wiki:Privacy policy">Privacy policy</a></li>
								<li id="footer-places-about"><a href="/index.php/Test_wiki:About" title="Test wiki:About">About test wiki</a></li>
								<li id="footer-places-disclaimer"><a href="/index.php/Test_wiki:General_disclaimer" title="Test wiki:General disclaimer">Disclaimers</a></li>
							</ul>
										<ul id="footer-icons" class="noprint">
										<li id="footer-poweredbyico">
						<a href="//www.mediawiki.org/"><img src="/resources/assets/poweredby_mediawiki_88x31.png" alt="Powered by MediaWiki" srcset="/resources/assets/poweredby_mediawiki_132x47.png 1.5x, /resources/assets/poweredby_mediawiki_176x62.png 2x" width="88" height="31"/></a>					</li>
									</ul>
						<div style="clear: both;"></div>
		</div>
		
<script>(window.RLQ=window.RLQ||[]).push(function(){mw.config.set({"wgBackendResponseTime":521});});</script>
	</body>
</html>

EOD;

		$expected = "%AError 400: Requested date '%s' not parseable.<br/><b>First Memento:</b> %s<br/><b>Last Memento:</b> %s<br/>%A";
		# It's the space between the br and /, note <br /> in our work and <br/> in their output
		$entity =<<<EOF
Error 400: Requested date 'bad-input' not parseable.<br/><b>First Memento:</b> <a rel="nofollow" class="external free" href="http://localhost:4455/index.php?title=Kevan_Lannister&amp;oldid=2">http://localhost:4455/index.php?title=Kevan_Lannister&amp;oldid=2</a><br/><b>Last Memento:</b> <a rel="nofollow" class="external free" href="http://localhost:4455/index.php?title=Kevan_Lannister&amp;oldid=127">http://localhost:4455/index.php?title=Kevan_Lannister&amp;oldid=127</a><br/>
EOF;

		$this->assertStringMatchesFormat( "%A" . $expected . "%A", $entity );
	}

};
