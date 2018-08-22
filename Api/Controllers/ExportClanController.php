<?php

require('Components/ExportClanComponent.php');

class ExportClanController 
{    
    public function __construct($config, $params)
    {
        try {
            $type = $params->get('type');
            $clanTag = $params->get('clanTag');
		}
		catch (Exception $e) {
			printf("Unable to get parameter: %s \n", $e->getMessage());
			exit();
        }
        
        $users = new ExportClan($config, $clanTag);
        
        switch (strtolower($type)) {
            case 'all':
                echo $users->getAllMembers();
                break;
            case 'distinct':
                echo $users->getDistinctMembers();
                break;
            default:
                printf("unknown type: [all|distinct]");
                break;
        }
    }
}