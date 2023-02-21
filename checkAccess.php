<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

/*
 * Checks if a given user (corresponding to a particular domain, be it SCS or CS) is a member of the "Wireguard" group in the Domain Controller.
 */
function checkWireguardAccess($usernameDomain,$usernameUID,$password)
{
	$preparedResponse = array(
		"msg" => "General exception",
		"code" => 255
	);

	$ldapServerArray = array(
		"scs" => "172.30.0.14",
		"cs" => "172.30.0.19",
	);

	////PROCESSING////
	$preparedResponse["msg"] = "Dummy OK";
	$preparedResponse["code"] = -1;

	if (!array_key_exists($usernameDomain,$ldapServerArray))
	{
		$preparedResponse["msg"] = "Specified domain does not exist";
		$preparedResponse["code"] = 2;
	}
	else
	{
		$ldapServer = $ldapServerArray[$usernameDomain];

		$ldaprdn = $usernameDomain . "\\" . $usernameUID;

		$ldapConnection = ldap_connect($ldapServer);

		ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);

		$bind = @ldap_bind($ldapConnection, $ldaprdn, $password);

		if ($bind)
		{
			$results = @ldap_search($ldapConnection,"DC=$usernameDomain,DC=ubbcluj,DC=ro","(samaccountname=$usernameUID)",array("memberof"));

			if (is_a($results,"LDAP\\Result"))
			{
				$entries = ldap_get_entries($ldapConnection, $results);
				if ($entries['count'] == 1)
				{
					$memberof = $entries['0']['memberof'];
					$memberofCount = $memberof['count'];

					$canWireguard = 0;
					for ($memberIterator = 0; $memberIterator < $memberofCount; $memberIterator++)
					{
						$memberofGroup = $memberof[$memberIterator];
						if (str_starts_with($memberofGroup,'CN=wireguard'))
						{
							$preparedResponse["msg"] = "Wireguard access permitted";
							$preparedResponse["domain"] = "$usernameDomain";
							$preparedResponse["code"] = 0;

							$canWireguard = 1;
							break;
						}
					}

					if (!$canWireguard)
					{
						$preparedResponse["msg"] = "This user can't access the Wireguard VPN";
						$preparedResponse["code"] = 7;
					}
				}
				else
				{
					$preparedResponse["msg"] = "Too many matching results";
					$preparedResponse["code"] = 6;
				}
			}
			else
			{
				$preparedResponse["msg"] = "Unexpected error during query";
				$preparedResponse["code"] = 5;
			}

			@ldap_close($ldapConnection);
		}
		else
		{
			$preparedResponse["msg"] = "Invalid username and password combination";
			$preparedResponse["code"] = 3;
		}
	}
	return $preparedResponse;
}
?>
