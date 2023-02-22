<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	global $standardServerParameters;

	//TODO Dynamically compute this from the actual server configuration
	$standardServerParameters=[];
	$standardServerParameters[0] = array(
		"baseIP"=>"10.0.16.2",
		"maxIP"=> "10.0.31.254",
		"netMask"=>"20",
		"pubKey"=>"ZBaK+E4YDFGGzWlG5cDYt/eFRQ6ajL7env4HYC7CK0E=");

?>
