<?php

class Bounds 
{

    private $configData;

    private $leagues = ["bronze", "silver", "gold", "platinum", "diamond", "master"];

    private $servers = ["us", "kr", "eu"];

    private $server;

    private $request;

    private $db;

    public function __construct($configName, $server) 
    {
        $this->configData = parse_ini_file($configName, TRUE);
        $this->server = $server;
        $this->request = new Request();
        $this->db = new Db($this->configData);
        $this->validate();
    }

    private function validate()
    {
        if (!in_array(strtolower($this->server), $this->servers)) {
            printf("Server (%s) does not exist", $this->server);
            exit();
        }
    }
    
    public function showBounds() 
    {
        printf("%s MMR Bounds\n\n", strtoupper($this->server));

        $bounds = $this->getServerBounds();

        echo $bounds;
    }

	private function getServerBounds() 
    {
		$this->db->connect();

		$query = "
			SELECT * 
            FROM `bounds` 
            WHERE `server` = ? 
            ORDER BY `league` DESC, `tier` ASC
		";

		$result = $this->db->doRawQuery($query, [$this->server]);

		$this->db->disconnect();

        $data = "";

        while ($row = $result->fetch_object()) {
            $data .= sprintf("%s[%s] %s\n", $this->leagues[$row->{'league'}], $row->{'tier'}, $row->{'ranges'});
        }

        return $data;
    }
}