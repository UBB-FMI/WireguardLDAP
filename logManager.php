<?php
	if (array_key_exists('REQUEST_METHOD', $_SERVER) && $_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

    require_once 'standardDefinitions.php';

    function _log($theMessage)
    {
        print($theMessage);
    }