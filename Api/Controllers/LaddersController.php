<?php

require('Components/LaddersComponent.php');

class LaddersController {
    
    public function __construct($config, $params) {
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
    }

}