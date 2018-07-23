<?php

require('Components/RaceImagesComponent.php');

class RaceImagesController {
    
    public function __construct($config, $params) {
        try {
            $race = $params->get('race');
		}
		catch (Exception $e) {
			printf("Unable to get parameter: %s \n", $e->getMessage());
			exit();
        }
        
        $races = new RaceImages($config, $race);

        $races->getImage(100, 100);

    }

}