<?php

	require 'deploymentCron.php';
	require 'deploymentRestore.php';

	// Check if the script is being called from the command line
	if (php_sapi_name() !== 'cli') 
	{
		die("This script can only be executed from the command line.");
	}

	// Check if an argument is provided
	if ($argc != 2)
	{
		die("Usage: php script.php <cron|boot>\n");
	}

	// Extract the argument
	$argument = strtolower($argv[1]);

	// Validate the argument
	if ($argument !== 'cron' && $argument !== 'boot') 
	{
		die("Invalid argument. Please use 'cron' or 'boot'.\n");
	}

	// Call the appropriate function based on the argument
	if ($argument === 'cron') 
	{
		_systemcall_cron();
	} 
	else 
	{
		_systemcall_boot();
	}
