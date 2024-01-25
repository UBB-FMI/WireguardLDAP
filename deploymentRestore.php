<?php
	if (array_key_exists('REQUEST_METHOD', $_SERVER) && $_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

    require_once 'standardDefinitions.php';
    require_once 'databaseManager.php';
    require_once 'logManager.php';
    require_once 'systemInteraction.php';

    function _systemcall_boot()
    {
        global $standardServerParameters;

        $allEntries = _dbFetchAllEntries();
        foreach ($allEntries as $entry) 
        {
            $entry_theUsername = $entry['theUsername'];
            $entry_clientPubKey = $entry['clientPubKey'];
            $entry_creationDate = $entry['creationDate'];
            $entry_addressOffset = $entry['addressOffset'];
            $entry_privilegeLevel = $entry['privilegeLevel'];

            $localServerParameters = $standardServerParameters[$entry_privilegeLevel];

            $baseIPAddress = ip2long($localServerParameters['baseIP']);
            $entry_actualIP = $baseIPAddress + (int)$entry_addressOffset;
            $entry_stringIP = long2ip($entry_actualIP);

            _log("Restoring (P".$entry_privilegeLevel."): ".$entry_theUsername." (".$entry_creationDate.") - ".long2ip($entry_actualIP)."\n");

            addWireguardPeer($localServerParameters['interface'],$entry_clientPubKey,$entry_stringIP);
        }
    }