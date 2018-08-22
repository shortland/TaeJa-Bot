<?php

class ExportClan
{
    private $configData;

    private $clanTag;

    private $request;

    private $db;

    public function __construct($configName, $clanTag)
    {
        $this->configData = parse_ini_file($configName, TRUE);
        $this->clanTag = $clanTag;
        $this->db = new Db($this->configData);
    }
    
    public function getAllMembers()
    {
		$this->db->connect();

		$query = "
            SELECT `real_name`, `mmr`, `league`, `tier`, `race`, `game_count`, `last_played_time_stamp`, `real_battle_tag`, `path`, `server`
            FROM `everyone`
			WHERE `clan_tag` = ?
            ORDER BY `server` DESC, `mmr` DESC
		";

		$result = $this->db->doRawQuery($query, [$this->clanTag]);

        $this->db->disconnect();

        $data = [];
        
        while ($row = $result->fetch_object()) {
            $data[] = $row;
        }

        $encoded = json_encode($data);
        return $encoded;
    }

    public function getDistinctMembers()
    {
		$this->db->connect();

		$query = "
			SELECT `real_name`, `mmr`, `league`, `tier`, `race`, `game_count`, `last_played_time_stamp`, `real_battle_tag`, `path`, `server`
			FROM `everyone`
			WHERE `clan_tag` = ?
            ORDER BY `server` DESC, `mmr` DESC
		";

		$result = $this->db->doRawQuery($query, [$this->clanTag]);

		$this->db->disconnect();

        $members = [];
        $tracked = [];
        while ($row = $result->fetch_object()) {
            if (!in_array($row->{'real_name'}, $tracked)) {
                $members[] = $row;
                $tracked[] = $row->{'real_name'};
            }
        }

        $encoded = json_encode($members);
        return $encoded;
    }
}