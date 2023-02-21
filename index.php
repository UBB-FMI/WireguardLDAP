<?php

	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	require_once 'checkAccess.php';
	require_once 'generateKeys.php';
	require_once 'configGenerator.php';
	require_once 'jsonBuilder.php';

	function validateInput($theData)
	{
		$theData = trim($theData);
		$theData = stripslashes($theData);
		$theData = htmlspecialchars($theData);
		return $theData;
	}

	$theDomain = validateInput($_POST["theDomain"]);
	$theUsername = validateInput($_POST["theUsername"]);
	$thePassword = $_POST["thePassword"];
	$theKeyPassword = $_POST["theKeyPassword"];

	if (strlen($theDomain) > 0 && strlen($theUsername) > 0 && strlen($thePassword) > 0 && strlen($theKeyPassword) > 0)
	{
		$checkAccess = checkWireguardAccess($theDomain,$theUsername,$thePassword);
		if ($checkAccess['code'] === 0)
		{
			$theKeyPair = generateWireguardKeypair(array($theDomain,$theUsername,$thePassword),$theKeyPassword);

			$deploymentResult = deployWireguardInstance($theDomain,$theUsername,$theKeyPair); aici am ramas, trebuie sa punem standard defs in asta noua care da deploy
		}
		else
		{
			$accessFailedMessage = "Something went wrong.";

			//Special cases messages
			switch ($checkAccess['code'])
			{
				case 7:
				case 3:
					$accessFailedMessage = $checkAccess['msg'];
					break;
			}
			echo buildJSONResponse($accessFailedMessage,1);
		}
	}
	else
	{
		echo buildJSONResponse("Invalid input data.",1);
	}
	//echo generateConfiguration(1,2,3,4,5);
?>
