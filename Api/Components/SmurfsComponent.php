<?php

class Smurfs {

    private $configData;

    private $clanTag;

    private $request;

    private $db;

    public function __construct($configName, $clanTag) {
        $this->configData = parse_ini_file($configName, TRUE);
        $this->clanTag = $clanTag;
        $this->request = new Request();
        $this->db = new Db($this->configData);
    }

    // function file_($contents){
    //     $parts = explode('/', $dir);
    //     file_put_contents("$dir/$file", $contents);
    // }
    
    public function listRelations() {
        printf("Analysis of members with clantag '%s'\n\n", $this->clanTag);
        $distinctUsers = $this->getUsersByClanTag();
        foreach ($distinctUsers as $user) {
            $this->getAccountsByBattleTag($user->{'battle_tag'});
        }
    }

    private function cleanBattleTag($battleTag) {
        preg_match('/^[^\\\_]+/', $battleTag, $matches);
        return $matches[0];
    }

    private function getAccountsByBattleTag($battleTag) {
        $cleaned = $this->cleanBattleTag($battleTag);
        echo "\n" . $cleaned . " is shared by:\n";
		$this->db->connect();

		$query = "
			SELECT 
                mmr, id, name, path, race, battle_tag, league, tier, clan_tag, server
			FROM 
				`everyone`
			WHERE
                `battle_tag` LIKE ?
            ORDER BY
                `mmr` DESC
        ";
        
        $almostLike = '%' . $cleaned . '%';

		$result = $this->db->doRawQuery($query, [$almostLike]);

		$this->db->disconnect();

        $savedIdentifiers = [];
        while ($row = $result->fetch_object()) {
            if ($row->{'clan_tag'}) {
                $row->{'clan_tag'} = '[' . $row->{'clan_tag'} . ']';
            }

            $wholeIdentifier = sprintf("%s%s (%s)", $row->{'clan_tag'}, $row->{'name'}, $row->{'server'});
            if (!in_array($wholeIdentifier, $savedIdentifiers)) {
                $savedIdentifiers[] = $wholeIdentifier;
                echo $wholeIdentifier . $row->{'league'} . $row->{'tier'} . $row->{'mmr'} . "\n";
            }
        }
    }

    /**
     * Returns array of distinct user objects (distinct in that no multi-race-mmr-ranking is shown)
     * 
     * @return array
     */
	private function getUsersByClanTag() {
		$this->db->connect();

		$query = "
			SELECT 
				mmr, id, name, path, race, battle_tag, league, tier, clan_tag, server
			FROM 
				`everyone`
			WHERE
                `clan_tag` = ?
            ORDER BY
                `mmr` DESC
		";

		$result = $this->db->doRawQuery($query, [$this->clanTag]);

		$this->db->disconnect();

        $nonDuplicates = [];
        $savedIds = [];
        while ($row = $result->fetch_object()) {
            if (!in_array($row->{'id'}, $savedIds)) {
                $savedIds[] = $row->{'id'};
                $nonDuplicates[] = $row;
            }
        }

        return $nonDuplicates;
    }
}