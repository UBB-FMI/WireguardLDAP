<?php

	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	require_once 'PHP-Curve25519/lib/Curve25519.php';

	function generateWireguardKeypair($theSeed,$keyPassword)
	{
		$theInstance = new Curve25519\Curve25519();

		$seedInitialization = "";
		//SEED THE SECRET
		foreach ($theSeed as $seedIterator)
		{
			$seedInitialization .= $seedIterator;
		}
		/////////////////
		$mySecret = hash_pbkdf2("sha512", $seedInitialization, $keyPassword, 4096,32,true);
		$mySecret = substr($mySecret,0,32);

		$myPublic = $theInstance->publicKey($mySecret);

		return array(base64_encode($mySecret),base64_encode($myPublic));
	}

