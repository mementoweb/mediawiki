<?php

require_once(__DIR__ . '/../tests/lib/HTTPFetch.php');

$sessionString = authenticateWithMediawiki();
$uagent = 'Memento Tester';
$ACCEPTDATETIME = 'Thu, 01 Jan 1970 00:00:00 GMT';

echo $sessionString;
