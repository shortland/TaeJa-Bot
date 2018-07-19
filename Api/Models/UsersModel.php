<?php

class Users {
	
	private $mmr;

	private $wins;

	private $losses;

	private $ties;

	private $points;

	private $longestWinStreak;

	private $currentWinStreak;

	private $currentRank;

	private $highestRank;

	private $previousRank;

	private $joinTimestamp;

	private $lastPlayedTimestamp;

	private $id;

	private $name;

	private $path;

	private $race;

	private $gameCount;

	private $battleTag;

	private $clanId;

	private $clanTag;

	private $clanName;

	private $clanIconUrl;

	private $clanDecalUrl;

	private $league;

	private $tier;

	private $server;

	private $serverCode;

	private $lastUpdate;

	public function getLastUpdate() {
		return $this->lastUpdate;
	}

	public function setLastUpdate($lastUpdate) {
		$this->lastUpdate = $lastUpdate;
	}

	public function getServer() {
		return $this->server;
	}

	public function setServer($server) {
		$this->server = $server;
	}

	public function getServerCode() {
		return $this->serverCode;
	}

	public function setServerCode($serverCode) {
		$this->serverCode = $serverCode;
	}

	public function getMmr() {
		return $this->mmr;
	}

	public function setMmr($mmr) {
		$this->mmr = $mmr;
	}

	public function getWins() {
		return $this->wins;
	}

	public function setWins($wins) {
		$this->wins = $wins;
	}

	public function getLosses() {
		return $this->losses;
	}

	public function setLosses($losses) {
		$this->losses = $losses;
	}

	public function getTies() {
		return $this->ties;
	}

	public function setTies($ties) {
		$this->ties = $ties;
	}

	public function getPoints() {
		return $this->points;
	}

	public function setPoints($points) {
		$this->points = $points;
	}

	public function getLongestWinStreak() {
		return $this->longestWinStreak;
	}

	public function setLongestWinStreak($longestWinStreak) {
		$this->longestWinStreak = $longestWinStreak;
	}

	public function getCurrentWinStreak() {
		return $this->currentWinStreak;
	}

	public function setCurrentWinStreak($currentWinStreak) {
		$this->currentWinStreak = $currentWinStreak;
	}

	public function getCurrentRank() {
		return $this->currentRank;
	}

	public function setCurrentRank($currentRank) {
		$this->currentRank = $currentRank;
	}

	public function getHighestRank() {
		return $this->highestRank;
	}

	public function setHighestRank($highestRank) {
		$this->highestRank = $highestRank;
	}

	public function getPreviousRank() {
		return $this->previousRank;
	}

	public function setPreviousRank($previousRank) {
		$this->previousRank = $previousRank;
	}

	public function getJoinTimestamp() {
		return $this->joinTimestamp;
	}

	public function setJoinTimestamp($joinTimestamp) {
		$this->joinTimestamp = $joinTimestamp;
	}

	public function getLastPlayedTimestamp() {
		return $this->lastPlayedTimestamp;
	}

	public function setLastPlayedTimestamp($lastPlayedTimestamp) {
		$this->lastPlayedTimestamp = $lastPlayedTimestamp;
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getPath() {
		return $this->path;
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function getRace() {
		return $this->race;
	}

	public function setRace($race) {
		$this->race = $race;
	}

	public function getGameCount() {
		return $this->gameCount;
	}

	public function setGameCount($gameCount) {
		$this->gameCount = $gameCount;
	}

	public function getBattleTag() {
		return $this->battleTag;
	}

	public function setBattleTag($battleTag) {
		$this->battleTag = $battleTag;
	}

	public function getClanId() {
		return $this->clanId;
	}

	public function setClanId($clanId) {
		$this->clanId = $clanId;
	}

	public function getClanTag() {
		return $this->clanTag;
	}

	public function setClanTag($clanTag) {
		$this->clanTag = $clanTag;
	}

	public function getClanName() {
		return $this->clanName;
	}

	public function setClanName($clanName) {
		$this->clanName = $clanName;
	}

	public function getClanIconUrl() {
		return $this->clanIconUrl;
	}

	public function setClanIconUrl($clanIconUrl) {
		$this->clanIconUrl = $clanIconUrl;
	}

	public function getClanDecalUrl() {
		return $this->clanDecalUrl;
	}

	public function setClanDecalUrl($clanDecalUrl) {
		$this->clanDecalUrl = $clanDecalUrl;
	}

	public function getLeague() {
		return $this->league;
	}

	public function setLeague($league) {
		$this->league = $league;
	}

	public function getTier() {
		return $this->tier;
	}

	public function setTier($tier) {
		$this->tier = $tier;
	}

	/**
	 * Returns this object as an array without keys
	 */
	public function toArray() {
		return [
			$this->getMmr(), 
			$this->getWins(), 
			$this->getLosses(), 
			$this->getTies(), 
			$this->getPoints(), 
			$this->getLongestWinStreak(), 
			$this->getCurrentWinStreak(), 
			$this->getCurrentRank(), 
			$this->getHighestRank(), 
			$this->getPreviousRank(), 
			$this->getJoinTimestamp(), 
			$this->getLastPlayedTimestamp(), 
			$this->getId(), 
			$this->getName() . $this->getServerCode(), 
			$this->getPath(), 
			$this->getRace(), 
			$this->getGameCount(), 
			$this->getBattleTag() . '\_' . $this->getRace() . '\_' . $this->getServer(), 
			$this->getLeague(), 
			$this->getTier(), 
			$this->getClanId(), 
			$this->getClanTag(), 
			$this->getClanName(), 
			$this->getClanIconUrl(), 
			$this->getClanDecalUrl(),
			$this->getServer(),
			$this->getLastUpdate(),
		];
	}

	/** 
	 * Returns this object as an array.
	 * Key names are identical to Db column names
	 */
	public function toKeyArray() {
		return [
			'mmr' 						=> $this->getMmr(),
			'wins' 						=> $this->getWins(),
			'losses' 					=> $this->getLosses(),
			'ties' 						=> $this->getTies(),
			'points' 					=> $this->getPoints(),
			'longest_win_streak' 		=> $this->getLongestWinStreak(),
			'current_win_streak' 		=> $this->getCurrentWinStreak(),
			'current_rank' 				=> $this->getCurrentRank(),
			'highest_rank' 				=> $this->getHighestRank(),
			'previous_rank' 			=> $this->getPreviousRank(),
			'join_time_stamp' 			=> $this->getJoinTimestamp(),
			'last_played_time_stamp' 	=> $this->getLastPlayedTimestamp(),
			'id' 						=> $this->getId(),
			'name' 						=> $this->getName(),
			'path' 						=> $this->getPath(),
			'race' 						=> $this->getRace(),
			'game_count' 				=> $this->getGameCount(),
			'battle_tag' 				=> $this->getBattleTag(),
			'league' 					=> $this->getLeague(),
			'tier' 						=> $this->getTier(),
			'clan_id' 					=> $this->getClanId(),
			'clan_tag' 					=> $this->getClanTag(),
			'clan_name' 				=> $this->getClanName(),
			'clan_icon_url' 			=> $this->getClanIconUrl(),
			'clan_decal_url' 			=> $this->getClanDecalUrl(),
			'server'					=> $this->getServer(),
			'last_update'				=> $this->getLastUpdate(),
		];
	}
}