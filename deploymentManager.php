<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	require_once 'standardDefinitions.php';
	require_once 'systemInteraction.php';

	function deployWireguardInstance($privilegeLevel,$theUsername,$theClientPublicKey)
	{
		global $standardServerParameters;
		$localServerParameters = $standardServerParameters[$privilegeLevel];

		$theDatabase = new SQLite3('test.db');
		if (_initializeTable($theDatabase) === false)
		{
			$toReturn['code'] = -1;
			$toReturn['msg'] = "Could not initialize database.";

			return $toReturn;
		}
		else
		{
			$currentAllocation = _fetchCurrentIPAllocation($theUsername,$privilegeLevel,$theDatabase);
			if ($currentAllocation['code'] === -1) //Fetch failed
			{
				$toReturn['code'] = -2;
				$toReturn['msg'] = "Could not check the current allocation status for this user and privilege.";

				return $toReturn;
			}
			else if ($currentAllocation['code'] === -2)
			{
				$allocationResult = _allocateIP($privilegeLevel,$theUsername,$theClientPublicKey,$theDatabase);

				if ($allocationResult['code'] !== 0)
				{
					$toReturn['code'] = -3;
					$toReturn['msg'] = $allocationResult['msg'];

					return $toReturn;
				}
				else
				{
					addWireguardPeer($localServerParameters['interface'],$theClientPublicKey,$allocationResult['ip']);

					$toReturn['code'] = 0;
					$toReturn['msg'] = "Successful insertion.";
					$toReturn['ip'] = $allocationResult['ip'];
					$toReturn['netmask'] = $allocationResult['netmask'];

					return $toReturn;
				}
			}
			else if ($currentAllocation['code'] === 0) //Something exists, update it
			{
				//TODO Handle update success, remove the old key first!!! From the actual system
				//You can get the data from the fetch attempt

				$userCheckingArray = $currentAllocation['data'];
				$updateResult = _updateIPAllocation($privilegeLevel,$theUsername,$theClientPublicKey,$userCheckingArray,$theDatabase);

				if ($updateResult['code'] === -1) //Update failed
				{
					$toReturn['code'] = -4;
					$toReturn['msg'] = "Failed to update the data for this user.";

					return $toReturn;
				}
				else
				{
					//TODO what happens if upon the completion of the update, the assigned IP changes?
					removeWireguardPeer($localServerParameters['interface'],$userCheckingArray['clientPubKey'],$updateResult['ip']);
					addWireguardPeer($localServerParameters['interface'],$theClientPublicKey,$updateResult['ip']);

					$toReturn['code'] = 0;
					$toReturn['msg'] = "Succesful update.";
					$toReturn['ip'] = $updateResult['ip'];
					$toReturn['netmask'] = $updateResult['netmask'];


					return $toReturn;
				}
			}
			else
			{
				$toReturn['code'] = -999;
				$toReturn['msg'] = "Unhandled deployment status.";

				return $toReturn;
			}
		}
	}

	function _initializeTable($theDatabase)
	{
		return $theDatabase->exec('CREATE TABLE IF NOT EXISTS "clientData" ( "theUsername" TEXT NOT NULL UNIQUE, "clientPubKey" TEXT NOT NULL, "creationDate" TEXT NOT NULL, "addressOffset" INTEGER NOT NULL, "privilegeLevel" INTEGER NOT NULL, PRIMARY KEY("theUsername") )');
	}

	function _fetchCurrentIPAllocation($theUsername,$privilegeLevel,$theDatabase)
	{
		$userCheckingStatement = $theDatabase->prepare('SELECT theUsername, clientPubKey, creationDate, addressOffset, privilegeLevel FROM clientData WHERE theUsername = :theUsername AND privilegeLevel=:privilegeLevel');
		$userCheckingStatement -> bindValue(':theUsername', $theUsername , SQLITE3_TEXT);
		$userCheckingStatement -> bindValue(':privilegeLevel', $privilegeLevel , SQLITE3_INTEGER);
		$userCheckingResult = $userCheckingStatement -> execute();

		if ($userCheckingResult === false)
		{
			$toReturn['code'] = -1;
			$toReturn['msg'] = "Could not check if the user already has an account.";

			return $toReturn;
		}
		else
		{
			$userCheckingArray = $userCheckingResult->fetchArray();
			if ($userCheckingArray['0'] === NULL) //Check if the user already has an account, and has merely regenerated the key
			{
				$toReturn['code'] = -2;
				$toReturn['msg'] = "Could not find the given user.";
			}
			else
			{
				$toReturn['code'] = 0;
				$toReturn['msg'] = "Success, found the user with the given privilege level.";
				$toReturn['data'] = $userCheckingArray;
			}
			$userCheckingStatement->close();
			return $toReturn;
		}
	}
	function _updateIPAllocation($privilegeLevel,$theUsername,$theClientPublicKey,$userCheckingArray,$theDatabase)
	{
		global $standardServerParameters;
		$baseIPAddress = ip2long($standardServerParameters[$privilegeLevel]['baseIP']);
		$maxIPAddress = ip2long($standardServerParameters[$privilegeLevel]['maxIP']);
		$actualNetmask = $standardServerParameters[$privilegeLevel]['netMask'];

		$userUpdateStatement = $theDatabase->prepare('UPDATE clientData SET clientPubKey=:clientPubKey, creationDate=:creationDate WHERE theUsername=:theUsername');
		$userUpdateStatement -> bindValue(':clientPubKey', $theClientPublicKey , SQLITE3_TEXT);
		$userUpdateStatement -> bindValue(':creationDate', date("d-m-Y") , SQLITE3_TEXT);
		$userUpdateStatement -> bindValue(':theUsername', $theUsername , SQLITE3_TEXT);
		$userUpdateResult = $userUpdateStatement -> execute();

		if ($userUpdateResult === false)
		{
			$toReturn['code'] = -1;
			$toReturn['msg'] = "Could not update the user data.";
		}
		else
		{
			$actualIPAddress = $baseIPAddress + (int)$userCheckingArray['addressOffset'];

			$toReturn['code'] = 0;
			$toReturn['msg'] = "Renewal succesful.";
			$toReturn['ip'] = long2ip($actualIPAddress);
			$toReturn['netmask'] = $actualNetmask;
		}
		$userUpdateStatement->close();
		return $toReturn;
	}
	function _allocateIP($privilegeLevel,$theUsername,$theClientPublicKey,$theDatabase)
	{
		global $standardServerParameters;
		$baseIPAddress = ip2long($standardServerParameters[$privilegeLevel]['baseIP']);
		$maxIPAddress = ip2long($standardServerParameters[$privilegeLevel]['maxIP']);
		$actualNetmask = $standardServerParameters[$privilegeLevel]['netMask'];


		//Offset calculation
		$theStatement = $theDatabase->prepare('SELECT MAX(addressOffset) FROM clientData WHERE privilegeLevel=:privilegeLevel');
		$theStatement -> bindValue(':privilegeLevel', $privilegeLevel , SQLITE3_INTEGER);

		$theStatementExecution = $theStatement->execute();
		if ($theStatementExecution === false)
		{
			$toReturn['code'] = -1;
			$toReturn['msg'] = "Database query failure.";

			return $toReturn;
		}
		else
		{
			$selectionResults = $theStatementExecution->fetchArray();
			if ($selectionResults['0'] === NULL)
			{
				$theNewOffset = 0;
			}
			else
			{
				//TODO Take into account expiring accounts, and simply provide the offset of the expired entry
				$theNewOffset = (int)$selectionResults[0] + 1; //This is int already, but just to be safe
			}

			//Offset calculation cleanup
			$theStatement->close();

			//IP calculation
			$actualIPAddress = $baseIPAddress + $theNewOffset;

			$toReturn = array();
			if ($actualIPAddress > $maxIPAddress)
			{
				$toReturn['code'] = -2;
				$toReturn['msg'] = "The VPN client list is full. No more IP addresses available.";

				return $toReturn;
			}
			else
			{
				$insertionStatement = $theDatabase->prepare('INSERT INTO clientData (theUsername, clientPubKey, creationDate, addressOffset, privilegeLevel) VALUES (:theUsername, :clientPubKey, :creationDate, :addressOffset, :privilegeLevel)');
				$insertionStatement -> bindValue(':theUsername', $theUsername , SQLITE3_TEXT);
				$insertionStatement -> bindValue(':clientPubKey', $theClientPublicKey , SQLITE3_TEXT);
				$insertionStatement -> bindValue(':creationDate', date("d-m-Y") , SQLITE3_TEXT);
				$insertionStatement -> bindValue(':addressOffset', $theNewOffset , SQLITE3_INTEGER);
				$insertionStatement -> bindValue(':privilegeLevel', $privilegeLevel , SQLITE3_INTEGER);

				$insertionResult = $insertionStatement -> execute();

				if ($insertionResult === false)
				{
					$toReturn['code'] = -3;
					$toReturn['msg'] = "Failed to insert specified data into the database.";

					return $toReturn;
				}
				else
				{
					$toReturn['code'] = 0;
					$toReturn['msg'] = "Allocation succesful.";
					$toReturn['ip'] = long2ip($actualIPAddress);
					$toReturn['netmask'] = $actualNetmask;

					return $toReturn;
				}
			}
		}
	}
?>
