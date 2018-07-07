<?php

require('HeaderFlags.php');
require('Parameters.php');
require('Ladders.php');

$header = new HeaderFlags('text', true);
$params = new Parameters();

try {
	$server = $params->get('server');
	$league = $params->get('league');
}
catch (Exception $e) {
	echo 'Unable to get parameters: ' . $e->getMessage() . "\n";
}

$ladders = new Ladders($server, $league);
$ladders->details();
$ladders->run();