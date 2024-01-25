<?php
	if (array_key_exists('REQUEST_METHOD', $_SERVER) && $_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

    require_once 'standardDefinitions.php';

    function _dbFetchAllEntries()
    {
        global $databasePath;
        $theDatabase = new SQLite3($databasePath);

        $fetchAllStatement = $theDatabase->prepare('SELECT theUsername, clientPubKey, creationDate, addressOffset, privilegeLevel FROM clientData');
        $fetchAllResult = $fetchAllStatement->execute();

        if ($fetchAllResult === false) 
        {
            return null;
        }
        else
        {
            $allEntries = array();
            while ($rowEntry = $fetchAllResult->fetchArray(SQLITE3_ASSOC)) 
            {
                $allEntries[] = $rowEntry; //Wtf is this, append?
            }
            $fetchAllStatement->close();

            return $allEntries;
        }
    }