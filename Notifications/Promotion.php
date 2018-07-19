<?php

require('../Ladders/HeaderFlags.php');
require('../Ladders/Parameters.php');
require('../Ladders/Db.php');
require('../Ladders/UserData.php');

$header = new HeaderFlags('text/html', true);
$params = new Parameters();
$config = '../config.ini';

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
            $userData->setRace($row->race);
            $userData->setClanTag($this->clanTag);
            
            $promoData = $userData->toKeyArray();
            $promoData['promoted_min_ago'] = round((time() - $row->join_time_stamp) / 60);
            $promoData['discord_id'] = $this->getDiscordName($row->battle_tag);

            $promosData[] = array_filter($promoData);

            $this->updateUserAlerted($row->battle_tag);
        }
        
        return $promosData;
    }

    private function getDiscordName(string $battleTag) {
        $this->db->connect();

        preg_match('/(^[0-9a-zA-Z\W\d]+)/', $battleTag, $match);
        $battleTagClean = str_replace("\\", "", $match[0]);

        $query = "
            SELECT 
                `id`
            FROM 
                `everyone_social` 
            WHERE 
                `battle_tag` = ?
        ";

        $result = $this->db->doRawQuery($query, [$battleTagClean]);

        $this->db->disconnect();

        $row = $result->fetch_object();
        
        if (!is_null($row)) {
            $discordTag = $row->{'id'};
        }
        else {
            $discordTag = "";
        }

        return $discordTag;
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
                `name`, `league`, `tier`, `join_time_stamp`, `battle_tag`, `path`, `server`, `race`
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
        $timeFrame = time() - 3600;

        $params = [$timeFrame, $this->clanTag];

        $result = $this->db->doRawQuery($query, $params);

        $this->db->disconnect();

        return $result;
    }

}