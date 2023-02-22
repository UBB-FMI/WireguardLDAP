<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	//TODO Handle error codes
	function addWireguardPeer($interfaceName,$clientPublicKey,$theClientIP)
	{
		shell_exec('sudo wg set '.$interfaceName.' peer "'.$clientPublicKey.'" allowed-ips '.$theClientIP.'/32');
		//Looks like Wireguard already sets up these rules for us, with wg-quick
		//shell_exec('sudo ip -4 route add '.$theClientIP.'/32 dev '.$interfaceName);
	}
	function removeWireguardPeer($interfaceName,$clientPublicKey,$theClientIP)
	{
		shell_exec('sudo wg set '.$interfaceName.' peer "'.$clientPublicKey.'" remove');
		//Looks like Wireguard already sets up these rules for us, with wg-quick
		//shell_exec('sudo ip -4 route delete '.$theClientIP.'/32 dev '.$interfaceName);
	}
?>
