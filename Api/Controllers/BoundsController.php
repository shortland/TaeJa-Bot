<?php

require('Components/BoundsComponent.php');

class BoundsController
{
    
    public function __construct($config, $params)
    {
        try {
            $server = $params->get('server');
		}
		catch (Exception $e) {
			printf("Unable to get parameter: %s \n", $e->getMessage());
			exit();
        }
        
        $bounds = new Bounds($config, $server);

        $bounds->showBounds();

    }

}