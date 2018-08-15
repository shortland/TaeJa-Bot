<?php

include 'Components/HeaderComponent.php';
include 'Components/ParametersComponent.php';

require('Components/DbComponent.php');
require('Components/RequestComponent.php');
require('Models/UsersModel.php');

require('Controllers/LaddersController.php');
require('Controllers/PromotionsController.php');
require('Controllers/LeagueImagesController.php');
require('Controllers/RaceImagesController.php');
require('Controllers/BattleNetController.php');
require('Controllers/SmurfsController.php');
require('Controllers/ClanSizeController.php');
require('Controllers/BoundsController.php');

$header = new Header('text', true);
$params = new Parameters();
$config = '../config.ini';

try {
	$endpoint = $params->get('endpoint');
}
catch (Exception $e) {
	printf("Please define an endpoint parameter");
	exit();
}

switch (strtolower($endpoint)) {
	case 'ladders': 
		new LaddersController($config, $params);
		break;
	case 'promotions':
		new PromotionsController($config, $params);
		break;
	case 'leagueimages':
		new LeagueImagesController($config, $params);
		break;
	case 'raceimages':
		new RaceImagesController($config, $params);
		break;
	case 'renewbattlenet':
		new BattleNetController($config, $params);
		break;
	case 'smurfs':
		new SmurfsController($config, $params);
		break;
	case 'clansize':
		new ClanSizeController($config, $params);
		break;
	case 'bounds':
		new BoundsController($config, $params);
		break;
	default:
		printf("Unknown endpoint [ladders|promotions|leagueimages|raceimages|renewbattlenet|smurfs|clansize|bounds]");
		break;
}

