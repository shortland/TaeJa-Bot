<?php

require('Components/LeagueImagesComponent.php');

class LeagueImagesController {
    
    public function __construct($config, $params) {
        try {
            $league = $params->get('league');
            $tier = $params->get('tier'); // later for getting other tiers
		}
		catch (Exception $e) {
			printf("Unable to get parameter: %s \n", $e->getMessage());
			exit();
        }
        
        $leagues = new LeagueImages($config, $league, $tier);

        $leagues->getImage(100, 100);

    }

}