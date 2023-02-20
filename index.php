<?php
	require_once 'checkAccess.php';
	require_once 'generateKeys.php';
	require_once 'standardDefinitions.php';
	require_once 'configGenerator.php';

	$theDomain="scs";
	$theUsername = "test";
	$thePassword = "macaroane";
	$theKeyPassword = "parola";

	$checkAccess = checkWireguardAccess($theDomain,$theUsername,$thePassword);
	if ($checkAccess['code'] === 0)
	{
		$theKeyPair = generateWireguardKeypair(array($theDomain,$theUsername,$thePassword),$theKeyPassword);

	}

	echo generateConfiguration(1,2,3,4,5);
?>
