<?php

require('Components/BattleNetComponent.php');

class BattleNetController
{
    
    public function __construct($config, $params)
    {
        try {
            $code = $params->get('code');
            $state = $params->get('state');
		}
		catch (Exception $e) {
			$parsedConfig = parse_ini_file($config, TRUE);
			$newLocation = sprintf(
			    "https://us.battle.net/oauth/authorize?client_id=%s&scope=sc2.profile&redirect_uri=%s&response_type=code&state=test",
                $parsedConfig['battlenet_api']['client_id'],
                $parsedConfig['battlenet_api']['redirect_url']
            );
            header(sprintf("Location: %s", $newLocation));
			exit();
        }
        
        $battleNet = new BattleNet($config, $code, $state);

        echo $battleNet->getNewAccessCode();

    }

}