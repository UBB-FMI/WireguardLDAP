<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	require_once 'standardDefinitions.php';

	deployWireguardInstance(0,"user","+caramida");

	function deployWireguardInstance($privilegeLevel,$theUsername,$theClientPublicKey)
	{
		$theDatabase = new SQLite3('test.db');
		_initializeTable($theDatabase);
		var_dump(_allocateIP($privilegeLevel,$theUsername,$theClientPublicKey,$theDatabase));
	}

	function _initializeTable($theDatabase)
	{
		$theDatabase->exec('CREATE TABLE IF NOT EXISTS "clientData" ( "theUsername" TEXT NOT NULL UNIQUE, "clientPubKey" TEXT NOT NULL, "creationDate" TEXT NOT NULL, "addressOffset" INTEGER NOT NULL, "privilegeLevel" INTEGER NOT NULL, PRIMARY KEY("theUsername") )');
	}

	function _allocateIP($privilegeLevel,$theUsername,$theClientPublicKey,$theDatabase)
	{
		$userCheckingStatement = $theDatabase->prepare('SELECT theUsername, clientPubKey, creationDate, addressOffset, privilegeLevel FROM clientData WHERE theUsername = :theUsername');
		$userCheckingStatement -> bindValue(':theUsername', $theUsername , SQLITE3_TEXT);
		$userCheckingResult = $userCheckingStatement -> execute();

		if ($userCheckingResult === false)
		{
			$toReturn['code'] = -1;
			$toReturn['msg'] = "Could not check if the user already has an account.";

			return $toReturn;
		}
		else
		{
			global $standardServerParameters;
			$baseIPAddress = ip2long($standardServerParameters[$privilegeLevel]['baseIP']);
			$maxIPAddress = ip2long($standardServerParameters[$privilegeLevel]['maxIP']);
			$actualNetmask = $standardServerParameters[$privilegeLevel]['netMask'];

			$userCheckingArray = $userCheckingResult->fetchArray();
			if ($userCheckingArray['0'] !== NULL) //Check if the user already has an account, and has merely regenerated the key
			{
				$userUpdateStatement = $theDatabase->prepare('UPDATE clientData SET clientPubKey=:clientPubKey, creationDate=:creationDate WHERE theUsername=:theUsername');
				$userUpdateStatement -> bindValue(':clientPubKey', $theClientPublicKey , SQLITE3_TEXT);
				$userUpdateStatement -> bindValue(':creationDate', date("d-m-Y") , SQLITE3_TEXT);
				$userUpdateStatement -> bindValue(':theUsername', $theUsername , SQLITE3_TEXT);
				$userUpdateResult = $userUpdateStatement -> execute();

				if ($userUpdateResult === false)
				{
					$toReturn['code'] = -2;
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

				$userCheckingStatement->close();
				$userUpdateStatement->close();
				return $toReturn;
			}
			else
			{
				//Offset calculation
				$theStatement = $theDatabase->prepare('SELECT MAX(addressOffset) FROM clientData WHERE privilegeLevel=:privilegeLevel');
				$theStatement -> bindValue(':privilegeLevel', $privilegeLevel , SQLITE3_INTEGER);

				$theStatementExecution = $theStatement->execute();
				if ($theStatementExecution === false)
				{
					$toReturn['code'] = -3;
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
						$toReturn['code'] = -4;
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
							$toReturn['code'] = -5;
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
		}
	}
?>
