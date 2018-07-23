<?php

class RaceImages {

    private $configData;

    private $races = ['terran', 'zerg', 'protoss', 'random'];

    private $race;

    private $sprite;

    public function __construct(
        string $configName,
        string $race
    ) {
        $this->configData = parse_ini_file($configName, TRUE);
        $this->race = strtolower($race);
        $this->validate();
    }

    public function getImage(int $width, int $height) {
        $localImage = $this->configData['bot_path']['url'] . "/Api/Static/Images/" . strtoupper($this->race) . ".png";
        $data = getimagesize($localImage);
        header("Content-type: {$data['mime']}");
        readfile($localImage);
    }

    private function validate() {
        if (!in_array($this->race, $this->races)) {
            printf("Unable to find '%s'. Not a race.\n", $this->race);
            exit();
        }
    }
}