<?php

	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	function buildJSONResponse($theMessage,$theCode,$data = NULL)
	{
		if ($data !== NULL)
		{
			$toReturn = array("msg"=>$theMessage,"code"=>$theCode,"data"=>$data);
		}
		else
		{
			$toReturn = array("msg"=>$theMessage,"code"=>$theCode);
		}
		return json_encode($toReturn);
	}

