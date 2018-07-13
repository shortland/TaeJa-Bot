<?php

require('HeaderFlags.php');
require('Parameters.php');
require('Ladders.php');

$header = new HeaderFlags('text', true);
$params = new Parameters();
$config = 'config.ini';

try {
	$server = $params->get('server');
	$league = $params->get('league');
}
catch (Exception $e) {
	printf("Unable to get parameters: %s \n", $e->getMessage());
	exit();
}

$ladders = new Ladders($config, $server, $league);
$ladders->run();