<?php

require('Components/SmurfsComponent.php');

class SmurfsController {
    
    public function __construct($config, $params) {
        try {
            $clanTag = $params->get('clanTag');
		}
		catch (Exception $e) {
			printf("Unable to get parameter: %s \n", $e->getMessage());
			exit();
        }
        
        $smurfs = new Smurfs($config, $clanTag);

        //$smurfs->getUsersByClanTag();
        $smurfs->listRelations();

    }

}