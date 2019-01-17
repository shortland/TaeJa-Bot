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

	/**
	 * @var string
	 */
	private $regionId;

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
				$this->regionId = '1';
				break;
			case 'eu':
				$this->serverCode = '02';
				$this->regionId = '2';
				break;
			case 'kr':
				$this->serverCode = '01';
				$this->regionId = '3';
				break;
		}

		$this->accessToken = $this->configData['battlenet_api']['access_code'];

		$this->request = new Request();

		$this->baseUrl = sprintf("https://%s.api.blizzard.com", $this->server);
	}
	
	/**
	 * Begin process of collecting and saving ladder details
	 */
	public function run() {
		printf("Begin save of '%s' server data for '%s' league\n", $this->server, $this->leagues[$this->league]);

		echo "Getting current season id\n";
		$currentSeasonId = $this->request->getJsonData(
			sprintf("%s/sc2/ladder/season/1?access_token=%s", $this->baseUrl, $this->accessToken)
		);

		if (!property_exists($currentSeasonId, 'seasonId')) {
			echo "Unable to get current seasonId. Actual response:\n";
			var_dump($currentSeasonId);
			exit();
		}
		$currentSeasonId = $currentSeasonId->{'seasonId'};

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
					sprintf("%s/sc2/legacy/ladder/%s/%s?access_token=%s", $this->baseUrl, $this->regionId, $ladderId, $this->accessToken)
				);

				for ($userNum = 0; $userNum < count($ladderContents->{'ladderMembers'}); ++$userNum) {
					$clanTag 		= '';
					$clanId 		= '';
					$clanName 		= '';
					$clanIconUrl 	= '';
					$clanDecalUrl 	= '';

					$user = $ladderContents->{'ladderMembers'}[$userNum];
					//var_dump($user);
					if (isset($user->{'character'}->{'clanTag'})) {
						$clanTag = $user->{'character'}->{'clanTag'};
						$clanId = 0;
						$clanName = $user->{'character'}->{'clanName'};
						$clanIconUrl = "";
						$clanDecalUrl = "";
					}
					$profileId = $user->{'character'}->{'id'};
					$realm = $user->{'character'}->{'realm'};

					$ladderDatas = $this->request->getJsonData(
						sprintf(
							"%s/sc2/profile/%s/%s/%s/ladder/%s?locale=en_US&access_token=%s",
							$this->baseUrl,
							$this->regionId,
							$realm,
							$profileId,
							$ladderId,
							$this->accessToken
						)
					)->{'ladderTeams'};

					$ladderDataUser;
					for ($ladderUserNum = 0; $ladderUserNum < count($ladderDatas); ++$ladderUserNum) {
						if ($ladderDatas[$ladderUserNum]->{'teamMembers'}[0]->{'id'} == $profileId) {
							$ladderDataUser = $ladderDatas[$ladderUserNum];
							break;
						}
					}

					//var_dump($ladderDataUser);

					$account = new Users();
					$account->setMmr($ladderDataUser->{'mmr'});
					$account->setWins($ladderDataUser->{'wins'});
					$account->setLosses($ladderDataUser->{'losses'});
					$account->setTies(0); #?
					$account->setPoints($ladderDataUser->{'points'});
					$account->setLongestWinStreak(0); #?
					$account->setCurrentWinStreak(0); #?
					$account->setCurrentRank(0); #?
					$account->setHighestRank($user->{'highestRank'});
					$account->setPreviousRank($ladderDataUser->{'previousRank'});
					$account->setJoinTimestamp($user->{'joinTimestamp'});
					$account->setLastPlayedTimestamp(0); #?
					$account->setId($profileId);
					$account->setName(addslashes($ladderDataUser->{'teamMembers'}[0]->{'displayName'}));
					$account->setPath(addslashes($user->{'character'}->{'profilePath'}));
					$account->setRace($ladderDataUser->{'teamMembers'}[0]->{'favoriteRace'});
					$account->setGameCount(($ladderDataUser->{'wins'} + $ladderDataUser->{'losses'}));
					$account->setRealBattleTag(addslashes($user->{'character'}->{'profilePath'})); #?
					$account->setBattleTag(addslashes($user->{'character'}->{'profilePath'})); #?
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
					$account->setRealName(addslashes($ladderDataUser->{'teamMembers'}[0]->{'displayName'}));

					//$alertedClan = $this->checkNewMember($account->getBattleTag(), ucfirst($account->getRace()), $account->getServer(), $account->getName(), $account->getClanTag());
					$account->setAlertedClan(1);

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
			`real_battle_tag`, 
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
			`alerted_clan`,
			`real_name`
		";

		$updateColumns = preg_replace('/\`,|\`\s|\`\z|\`\n/', '` = ?,', $columnNames);
		$updateColumns = preg_replace('/,\s+\z/', '', $updateColumns);

		$query = "
			INSERT INTO `everyone` 
				( $columnNames )
			VALUES 
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
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
	// private function setLastUpdate() {
	// 	$last = fopen('../lastupdate.txt', 'w');
	// 	fwrite($last, time());
	// 	fclose($last);
	// }

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