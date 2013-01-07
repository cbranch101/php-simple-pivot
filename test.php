<?php

	require_once('../php-underscore/underscore.php');
	require_once('simple_pivot.php');
	
	$arrayInput = array(
		array(
			'impressions' => 100,
			'clicks' => 200,
			'fans' => 50,
		),
		array(
			'impressions' => 100,
			'clicks' => 100,
			'fans' => 50,
		),
		array(
			'impressions' => 100,
			'clicks' => 100,
			'fans' => 50,
		),
	);
	
	$input = __::chain($arrayInput);
	
	
	$values = array(
		array(
			'field' => 'clicks',
			'calc' => 'average_count',
		),
	);
	
	$data = Simple_Pivot::calculateValues($values, $input);
	
	echo json_encode($data);
