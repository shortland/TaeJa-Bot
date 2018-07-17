<?php

require('../Ladders/HeaderFlags.php');
require('../Ladders/Parameters.php');
require('../Ladders/Db.php');
require('../Ladders/UserData.php');

$header = new HeaderFlags('text/html', true);
$params = new Parameters();
$config = '../Ladders/config.ini';

try {
    $clanTag = $params->get('clanTag');
}
catch (Exception $e) {
	printf("Unable to get parameters: %s \n", $e->getMessage());
	exit();
}

$promotions = new Promotion($config, $clanTag);

$promotedUsers = $promotions->getUsers();

echo json_encode($promotedUsers, JSON_UNESCAPED_UNICODE);

class Promotion {

    private $db;

    private $configData;

    private $clanTag;

    public function __construct(
        string $configName,
        string $clanTag
    ) {
        $this->configData = parse_ini_file($configName, TRUE);
        $this->clanTag = $clanTag;
        $this->db = new Db($this->configData);
    }

    /**
     * Returns comma delimited list of users that are in $this->clanTag which have been promoted within the last 60 minutes.
     */
    public function getUsers() {
        $usersData = $this->getUsersData();

        $promosData = [];

        while ($row = $usersData->fetch_object()) {
            $userData = new UserData();

            $userData->setName($row->name);
            $userData->setLeague($row->league);
            $userData->setTier($row->tier);
            $userData->setJoinTimestamp($row->join_time_stamp);
            $userData->setBattleTag($row->battle_tag);
            $userData->setPath($row->path);
            $userData->setServer($row->server);
            
            $promoData = $userData->toKeyArray();
            $promoData['promoted_min_ago'] = round((time() - $row->join_time_stamp) / 60);
            
            $promosData[] = array_filter($promoData);
        }

        return $promosData;
    }

    private function updateUserAlerted(string $battleTag) {
        $this->db->connect();

        $query = "
            UPDATE
                `everyone`
            SET
                `alerted` = '1'
            WHERE
                `battle_tag` = ?
        ";

        $this->db->doRawQuery($query, [$battleTag]);

        $this->db->disconnect();
    }

    private function getUsersData() {
        $this->db->connect();

        $query = "
            SELECT 
                `name`, `league`, `tier`, `join_time_stamp`, `battle_tag`, `path`, `server`
            FROM 
                `everyone` 
            WHERE 
                `join_time_stamp` > ? 
                AND 
                    alerted = '0' 
                AND 
                    clan_tag = ?
        ";

        // Get list of users promoted within last 3600 seconds. AKA last hour.
        $timeFrame = time() - 86400;

        $params = [$timeFrame, $this->clanTag];

        $result = $this->db->doRawQuery($query, $params);

        $this->db->disconnect();

        return $result;
    }

}