<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	global $theServerIP;
	global $standardServerParameters;

	//TODO Dynamically compute this from the actual server configuration
	$theServerIP="172.30.118.184";

	$standardServerParameters=[];
	$standardServerParameters[0] = array(
		"interface"=>"privileged",
		"baseIP"=>"10.0.16.1",
		"maxIP"=> "10.0.31.254",
		"netMask"=>"20",
		"pubKey"=>"ZBaK+E4YDFGGzWlG5cDYt/eFRQ6ajL7env4HYC7CK0E=",
		"port"=>"51820");

?>
