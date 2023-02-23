<?php

	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	require_once '../checkAccess.php';
	require_once '../generateKeys.php';
	require_once '../configGenerator.php';
	require_once '../jsonBuilder.php';
	require_once '../deploymentManager.php';

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

			$clientPrivateKey = $theKeyPair[0];
			$clientPublicKey = $theKeyPair[1];

			$privilegeLevel = 1;
			switch($theDomain)
			{
				case "cs":
					$privilegeLevel = 0;
					break;
				default:
					$privilegeLevel = 1;
					break;
			}

			$deploymentResult = deployWireguardInstance($privilegeLevel,$theUsername,$clientPublicKey);

			if ($deploymentResult['code'] === 0)
			{
				$generatedConfiguration = generateConfiguration($privilegeLevel,$deploymentResult['ip'],$deploymentResult['netmask'],$clientPrivateKey);

				echo buildJSONResponse("Generation complete.",0,base64_encode($generatedConfiguration));
			}
			else
			{
				echo buildJSONResponse($deploymentResult['msg'],2);
			}
		}
		else
		{
			echo buildJSONResponse($checkAccess['msg'],1);
		}
	}
	else
	{
		echo buildJSONResponse("Invalid input data.",1);
	}
?>
