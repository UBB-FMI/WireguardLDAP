<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	//TODO Handle error codes
	function addWireguardPeer($interfaceName,$clientPublicKey,$theClientIP,$theNetmask)
	{
		shell_exec('sudo wg set '.$interfaceName.' peer "'.$clientPublicKey.'" allowed-ips '.$theClientIP.'/'.$theNetmask);
		shell_exec('sudo ip -4 route add '.$theClientIP.'/'.$theNetmask' dev '.$interfaceName);
	}
	function addWireguardPeer($interfaceName,$clientPublicKey,$theClientIP,$theNetmask)
	{
		shell_exec('sudo wg set '.$interfaceName.' peer "'.$clientPublicKey.'" remove');
		shell_exec('sudo ip -4 route delete '.$theClientIP.'/'.$theNetmask' dev '.$interfaceName);
	}
?>
