<?php
	if (array_key_exists('REQUEST_METHOD', $_SERVER) && $_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	global $theServerIP;
	global $standardServerParameters;
	global $databasePath;

	global $msTenantDetails;

	//TODO Dynamically compute this from the actual server configuration
	$theServerIP="193.231.20.20";
//	$theServerIP="86.127.67.140";
	$databasePath="/srv/wireguard_fmi/theDB.db";

	$standardServerParameters=[];
	$standardServerParameters[0] = array(
		"interface"=>"privileged",
		"baseIP"=>"10.0.16.2",
		"maxIP"=> "10.0.31.254",
		"netMask"=>"20",
		"pubKey"=>"ZBaK+E4YDFGGzWlG5cDYt/eFRQ6ajL7env4HYC7CK0E=",
		"port"=>"51830",
		"allowedIPs"=>"0.0.0.0/0"
	);
	$standardServerParameters[1] = array(
		"interface"=>"unprivileged",
		"baseIP"=>"10.0.32.2",
		"maxIP"=> "10.0.47.254",
		"netMask"=>"20",
		"pubKey"=>"KkxqXijYvoAl96xgVUOwl1J8yz9f48z1/fZH0HucnkU=",
		"port"=>"51831",
		"allowedIPs"=>"172.30.0.0/16, 131.159.8.236/32, 193.231.20.142/32"
	);

	$msTenantDetails=[];
	$msTenantDetails = array(
		"tenantID"       => "5a4863ed-40c8-4fd5-8298-fbfdb7f13095",
		"client_id"      => "5ef3d24d-9c73-4c1e-b36b-658731a8a1fb",
		"scope"          => "openid profile offline_access",
		"redirect_uri"   => "https://www.cs.ubbcluj.ro/vpn/",
		"client_secret"  => "HAHANO"
	);




