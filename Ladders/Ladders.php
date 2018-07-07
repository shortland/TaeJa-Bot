<?php

class Ladders {
	
	/**
	 * @var string
	 */
	private $server;

	/**
	 * @var array
	 */
	private $servers = ['us', 'kr', 'eu'];

	/** 
	 * @var string
	 */
	private $league;

	/**
	 * @var array
	 */
	private $leagues = ['bronze', 'silver', 'gold', 'platinum', 'diamond', 'master', 'grandmaster'];
	
	public function __construct(string $server, string $league) {
		$this->league = $league;
		$this->server = $server;
	}

	public function details() {
		$message = "Server %s\nLeague %s";
		echo sprintf($message, $this->server, $this->league);
	}
	
	public function run() {
		try {
			$this->validate();
		}
		catch(Exception $e) {
			echo 'Unable to run: ' . $e->getMessage() . "\n";
		}
	}

	/**
	 * @throws Exception
	 */
	private function validate() {
		if (!in_array($this->server, $this->servers)) {
			throw new Exception('Invalid server choice');
		}
		if ($this->league < 0 || $this->league > 6) {
			throw new Exception('Invalid league choice');
		}
	}
}