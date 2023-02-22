<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	global $standardIPRanges;
	global $serverPublicKey;

	$serverPublicKey="asdasda";
	$standardIPRanges=[];
	$standardIPRanges[0] = ["10.0.16.2","10.0.31.254"];

?>
