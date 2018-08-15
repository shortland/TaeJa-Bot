<?php

require('Components/BattleNetComponent.php');

class BattleNetController {
    
    public function __construct($config, $params) {
        try {
            $code = $params->get('code');
            $state = $params->get('state');
		}
		catch (Exception $e) {
			printf("Unable to get parameter: %s \n", $e->getMessage());
			exit();
        }
        
        $battleNet = new BattleNet($config, $code, $state);

        echo $battleNet->getNewAccessCode();

    }

}