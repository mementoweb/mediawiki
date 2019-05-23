<?php

require_once __DIR__ . '/../tests/lib/HTTPFetch.php';

$sessionString = authenticateWithMediawiki();

echo $sessionString;
