<?php
include 'Curve25519.php';

function generateWireguardKeypair()
{
	$theInstance = new Curve25519();

	$newSecret
	$mySecret = random_bytes(32);
	$myPublic = $theInstance->publicKey($mySecret);
}

var_dump(base64_encode($myPublic));
?>
