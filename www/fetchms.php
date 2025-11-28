<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') { exit; }

	require_once "../standardDefinitions.php";

	header('Content-Type: application/json');

	$tenantId = $msTenantDetails['tenantID'];
	$clientId = $msTenantDetails['client_id'];
	$scope = $msTenantDetails['scope'];

	$responseType = 'code';
	$responseMode = 'query';

	if ($tenantId === '' || $clientId === '' || $scope === '')
	{
		http_response_code(500);
		echo json_encode([
			'error' => 'Microsoft authentication configuration is not available.'
		]);
		exit;
	}

	echo json_encode([
		'tenantId' => $tenantId,
		'clientId' => $clientId,
		'scope' => $scope,
		'responseType' => $responseType,
		'responseMode' => $responseMode
	]);
