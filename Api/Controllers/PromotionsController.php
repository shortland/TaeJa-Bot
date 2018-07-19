<?php

require('Components/PromotionsComponent.php');

class PromotionsController {
    
    public function __construct($config, $params) {
        try {
			$clanTag = $params->get('clanTag');
		}
		catch (Exception $e) {
			printf("Unable to get parameter clanTag: %s \n", $e->getMessage());
			exit();
        }
        
		$promotions = new Promotions($config, $clanTag);
		echo $promotions->run();
    }

}