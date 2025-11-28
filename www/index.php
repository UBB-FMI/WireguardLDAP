<?php

	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	require_once '../checkAccess.php';
	require_once '../generateKeys.php';
	require_once '../configGenerator.php';
	require_once '../jsonBuilder.php';
	require_once '../deploymentManager.php';
	require_once '../microsoftAPI.php';

	function validateInput($theData)
	{
		$theData = trim($theData);
		$theData = stripslashes($theData);
		$theData = htmlspecialchars($theData);
		return $theData;
	}

	function random_string(int $length = 32): string 
	{
    	return bin2hex(random_bytes($length / 2));
	}

	$microsoftCode 	= $_POST["code"];
	$otherUser 		= $_POST["otherName"];

	if (strlen($microsoftCode) > 0)
	{
		//===== FETCH EMAIL =====
		$theKeyPassword = random_string();
		$microsoftEmailData = fetchEmailAddress($microsoftCode);
		if ($microsoftEmailData[0] == false)
		{
			echo buildJSONResponse($microsoftEmailData[1],1);
			exit;
		}

		$microsoftEmail = $microsoftEmailData[1];

		//===== EMAIL IS VALID HERE =====
		$theKeyPair = generateWireguardKeypair([$microsoftEmail],$theKeyPassword);

		$clientPrivateKey = $theKeyPair[0];
		$clientPublicKey = $theKeyPair[1];

		if (str_ends_with($microsoftEmail, '@ubbcluj.ro')) 
		{
			$privilegeLevel = 0;
		}
		elseif (str_ends_with($microsoftEmail, '@stud.ubbcluj.ro')) 
		{
			$privilegeLevel = 1;
		} 
		else 
		{
			echo buildJSONResponse("Could not determine your privilege level.",1);
			exit;
		}

		//===== BUILD USERNAME =====
		$baseUsername = preg_replace('/[^a-zA-Z0-9]/', '_', $microsoftEmail);
		$basePrivilegeLevel = $privilegeLevel;
		if (strlen($otherUser) == 0)
		{
			$effectiveUsername = $baseUsername;
			$effectivePrivilegeLevel = $basePrivilegeLevel;
		}
		else
		{
			if ($privilegeLevel == 0)
			{
				$effectiveUsername = $baseUsername . "_other_" . $otherUser;
				$effectivePrivilegeLevel = 1;
			}
			else
			{
				echo buildJSONResponse("You don't have the permission to generate a VPN configuration for someone else.",1);
				exit;
			}
		}

		//===== GENERATE KEYS =====
		$deploymentResult = deployWireguardInstance($effectivePrivilegeLevel,$effectiveUsername,$clientPublicKey);

		if ($deploymentResult['code'] === 0)
		{
			$generatedConfiguration = generateConfiguration($effectivePrivilegeLevel,$deploymentResult['ip'],$deploymentResult['netmask'],$clientPrivateKey);

			echo buildJSONResponse("Generation complete.",0,base64_encode($generatedConfiguration));
		}
		else
		{
			echo buildJSONResponse($deploymentResult['msg'],2);
		}
	}
	else
	{
		echo buildJSONResponse("Invalid input data.",1);
	}
?>
