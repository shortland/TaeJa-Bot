<?php

class Ladders {

	/** 
	 * @var array
	 */
	private $configData;
	
	/**
	 * @var Db
	 */
	private $db;

	/**
	 * @var string
	 */
	private $server;

	/**
	 * @var array
	 */
	private $servers = ['us', 'kr', 'eu'];

	/**
	 * @var int
	 */
	private $serverCode;

	/** 
	 * @var int
	 */
	private $league;

	/**
	 * @var array
	 */
	private $leagues = ['bronze', 'silver', 'gold', 'platinum', 'diamond', 'master', 'grandmaster'];
	
	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var string
	 */
	private $baseUrl;

	public function __construct(
		string $configName,
		string $server, 
		string $league
	) {
		$this->configData = parse_ini_file($configName, TRUE);
		$this->db = new Db($this->configData);
		$this->league = $league;
		$this->server = strtolower($server);

		try {
			$this->validate();
		}
		catch(Exception $e) {
			printf("Unable to create 'Ladders' object: %s\n", $e->getMessage());
			exit();
		}

		switch ($this->server) {
			case 'us':
				$this->serverCode = '00';
				break;
			case 'eu':
				$this->serverCode = '02';
				break;
			case 'kr':
				$this->serverCode = '01';
				break;
		}

		$this->accessToken = $this->configData['battlenet_api']['access_code'];

		$this->request = new Request();

		$this->baseUrl = sprintf("https://%s.api.battle.net", $this->server);
	}
	
	/**
	 * Begin process of collecting and saving ladder details
	 */
	public function run() {
		printf("Begin save of '%s' server data for '%s' league\n", $this->server, $this->leagues[$this->league]);

		echo "Getting current season id\n";
		$currentSeasonId = $this->request->getJsonData(
			sprintf("%s/data/sc2/season/current?access_token=%s", $this->baseUrl, $this->accessToken)
		)->{'id'};

		echo "Getting list of league ladder divisions\n";
		$laddersData = $this->request->getJsonData(
			sprintf("%s/data/sc2/league/%s/201/0/%s?access_token=%s", $this->baseUrl, $currentSeasonId, $this->league, $this->accessToken)
		);

		echo "Saving each league tier mmr boundaries\n";
		$tiers = 0;
		if ($this->league != 6) {
			for ($i = 0; $i <= 2; ++$i) {
				$tierData = $laddersData->{'tier'}[$i]->{'min_rating'} . ' - ' . $laddersData->{'tier'}[$i]->{'max_rating'};
				$this->saveBounds($tierData, 1 + $i);
			}
			$tiers = 2;
		}

		echo "Iterating through each tier->ladder division->user\n";
		
		for ($tier = 0; $tier <= $tiers; ++$tier) {
			
			printf("Iterating through league tier #%d\n", $tier + 1);

			for ($ladderNum = 0; $ladderNum < count($laddersData->{'tier'}[$tier]->{'division'}); ++$ladderNum) {
				
				$ladderId = $laddersData->{'tier'}[$tier]->{'division'}[$ladderNum]->{'ladder_id'};
				
				printf("Iterating though ladder division id %s\n", $ladderId);

				$ladderContents = $this->request->getJsonData(
					sprintf("%s/data/sc2/ladder/%s?access_token=%s", $this->baseUrl, $ladderId, $this->accessToken)
				);

				for ($userNum = 0; $userNum < count($ladderContents->{'team'}); ++$userNum) {
					$clanTag 		= '';
					$clanId 		= '';
					$clanName 		= '';
					$clanIconUrl 	= '';
					$clanDecalUrl 	= '';

					$user = $ladderContents->{'team'}[$userNum];
					
					if (isset($user->{'member'}[0]->{'clan_link'}->{'clan_tag'})) {
						$clanTag = $user->{'member'}[0]->{'clan_link'}->{'clan_tag'};
						$clanId = $user->{'member'}[0]->{'clan_link'}->{'id'};
						$clanName = $user->{'member'}[0]->{'clan_link'}->{'clan_name'};
						
						if (isset($user->{'member'}[0]->{'clan_link'}->{'icon_url'})) {
							$clanIconUrl = $user->{'member'}[0]->{'clan_link'}->{'icon_url'};
						}

						if (isset($user->{'member'}[0]->{'clan_link'}->{'decal_url'})) {
							$clanDecalUrl = $user->{'member'}[0]->{'clan_link'}->{'decal_url'};
						}
					}

					$account = new Users();

					$account->setMmr($user->{'rating'});
					$account->setWins($user->{'wins'});
					$account->setLosses($user->{'losses'});
					$account->setTies($user->{'ties'});
					$account->setPoints($user->{'points'});
					$account->setLongestWinStreak($user->{'longest_win_streak'});
					$account->setCurrentWinStreak($user->{'current_win_streak'});
					$account->setCurrentRank($user->{'current_rank'});
					$account->setHighestRank($user->{'highest_rank'});
					$account->setPreviousRank($user->{'previous_rank'});
					$account->setJoinTimestamp($user->{'join_time_stamp'});
					$account->setLastPlayedTimestamp($user->{'last_played_time_stamp'});
					$account->setId($user->{'member'}[0]->{'legacy_link'}->{'id'});
					$account->setName(addslashes($user->{'member'}[0]->{'legacy_link'}->{'name'}));
					$account->setPath(addslashes($user->{'member'}[0]->{'legacy_link'}->{'path'}));
					$account->setRace($user->{'member'}[0]->{'played_race_count'}[0]->{'race'}->{'en_US'});
					$account->setGameCount($user->{'member'}[0]->{'played_race_count'}[0]->{'count'});
					$account->setBattleTag($user->{'member'}[0]->{'character_link'}->{'battle_tag'});
					$account->setLeague($this->leagues[$this->league]);
					$account->setTier($tier + 1);
					$account->setClanId($clanId);
					$account->setClanTag($clanTag);
					$account->setClanName(addslashes($clanName));
					$account->setClanIconUrl(addslashes($clanIconUrl));
					$account->setClanDecalUrl(addslashes($clanDecalUrl));
					$account->setServer($this->server);
					$account->setServerCode($this->serverCode);
					$account->setLastUpdate(time());

					$alertedClan = $this->checkNewMember($account->getBattleTag(), ucfirst($account->getRace()), $account->getServer(), $account->getName(), $account->getClanTag());
					$account->setAlertedClan($alertedClan);


					$this->saveUser($account);

					printf("Updated %s\n\n", $account->getName());
				}
			}
		}
	}

	/**
	 * Begin process of checking to see if someone recently join a clan
	 * 
	 * TODO: BUG: if they were in the clan already, but unranked, will throw error?
	 * -> attempt to patch in commented section below
	 */
	private function checkNewMember($battleTag, $race, $server, $username, $currentClanTag) {
		$fullBattleTag = ($battleTag . '\_' . $race . '\_' . $server . '\_' . $username);

		$previousClanTag = $this->getClanTag($fullBattleTag);

		/** 
		 * attempt to fix the situation in which the person was in the clan already, \
		 * but unranked -> then became ranked... it would 'see' it as if they just joined the clan even though they only just now got ranked. 
		*/
		if ($previousClanTag == 'untracked_user') {
			// Was already in clan, but unranked so data wasn't tracked
			// echo "[" . $currentClanTag . "] prev. [" . $previousClanTag . "]\n";
		}
		elseif ($currentClanTag != $previousClanTag) {
			// Recently joined clan
			echo "[" . $currentClanTag . "] prev. [" . $previousClanTag . "]\n";
			return 1;
		}
		elseif ($currentClanTag == $previousClanTag) {
			// In the same clan
			// echo "[" . $currentClanTag . "] prev. [" . $previousClanTag . "]\n";
		} 
		else {
			// Not in a clan
			// Not reached, instead hit at case 2 current != previous
			// echo "[" . $currentClanTag . "] prev. [" . $previousClanTag . "]\n";
		}

		return 0;
	}

	/**
	 * Get a user's clantag which is stored in the Database (aka the clan_tag from previous update)
	 */
	private function getClanTag($battleTag) {
		$this->db->connect();

		$query = "
			SELECT 
				`clan_tag`
			FROM 
				`everyone`
			WHERE
				`battle_tag` = ?
		";

		$result = $this->db->doRawQuery($query, [$battleTag]);

		$this->db->disconnect();

		$row = $result->fetch_object();

		if (is_null($row)) {
			return "untracked_user";
		}

		return $row->{'clan_tag'};
	}

	/**
	 * Save user's data to the db
	 */
	private function saveUser($account) {
		$this->db->connect();

		$columnNames = "
			`mmr`, 
			`wins`, 
			`losses`, 
			`ties`, 
			`points`, 
			`longest_win_streak`, 
			`current_win_streak`, 
			`current_rank`, 
			`highest_rank`, 
			`previous_rank`, 
			`join_time_stamp`, 
			`last_played_time_stamp`, 
			`id`, 
			`name`, 
			`path`, 
			`race`, 
			`game_count`, 
			`battle_tag`, 
			`league`, 
			`tier`, 
			`clan_id`, 
			`clan_tag`, 
			`clan_name`, 
			`clan_icon_url`, 
			`clan_decal_url`, 
			`server`,
			`last_update`,
			`alerted_clan`
		";

		$updateColumns = preg_replace('/\`,|\`\s|\`\z|\`\n/', '` = ?,', $columnNames);
		$updateColumns = preg_replace('/,\s+\z/', '', $updateColumns);

		$query = "
			INSERT INTO `everyone` 
				( $columnNames )
			VALUES 
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
			ON DUPLICATE KEY UPDATE 
				$updateColumns
		";

		$result = $this->db->doRawQuery($query, 
			array_merge(
				$account->toArray(), 
				$account->toArray()
			)
		);

		$this->db->disconnect();
	}

	/**
	 * Saves the MMR bounds for a league's tier
	 */
	private function saveBounds($tierData, $tierNum) {
		$this->db->connect();

		$query = "
			INSERT INTO `bounds` 
				(`server`, `league`, `tier`, `ranges`, `identifier`) 
			VALUES 
				(?, ?, ?, ?, ?) 
			ON DUPLICATE KEY UPDATE 
				`ranges` = ?
		";
			
		$result = $this->db->doRawQuery($query, [
			$this->server, 
			$this->league, 
			$tierNum,
			$tierData, 
			sprintf("%s %s %s", $this->server, $this->league, $tierNum),
			$tierData
		]);

		$this->db->disconnect();
	}

	// $this->db->connect();
	// $query = "SELECT * FROM `everyone` WHERE `name` LIKE '%shortland%' ORDER BY `mmr` DESC";
	// $result = $this->db->doRawQuery($query, []);
	// while ($row = $result->fetch_object()){
	// 	var_dump($row);
	// }

	/**
	 * Sets the current time of the scripts execution to lastupdate.txt
	 * 'lastupdate.txt' is used by the discord bot to display the last time
	 * the server/db was updated
	 * 
	 * @TODO: make have a field in constructor for the path to the file instead
	 * of hardcoding it here?
	 */
	private function setLastUpdate() {
		$last = fopen('../lastupdate.txt', 'w');
		fwrite($last, time());
		fclose($last);
	}

	/**
	 * @throws Exception
	 */
	private function validate() {
		if (!in_array(strtolower($this->server), $this->servers)) {
			throw new Exception('Invalid server choice');
		}
		if ($this->league < 0 || $this->league > 6) {
			throw new Exception('Invalid league choice');
		}
	}
}