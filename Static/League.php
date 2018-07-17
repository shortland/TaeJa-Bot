<?php

require('../Ladders/HeaderFlags.php');
require('../Ladders/Parameters.php');
require('../Ladders/Request.php');

$header = new HeaderFlags('image', true);
$params = new Parameters();
$config = '../Ladders/config.ini';

try {
    $league = $params->get('league');
}
catch (Exception $e) {
	printf("Unable to get parameters: %s \n", $e->getMessage());
	exit();
}

$leagues = new League($config, $league);

$promotedUsers = $leagues->getImage(100, 100);

class League {

    private $configData;

    private $request;

    private $league;

    private $sprite;

    public function __construct(
        string $configName,
        string $league
    ) {
        $this->configData = parse_ini_file($configName, TRUE);
        $this->request = new Request();
        $this->league = strtolower($league);
        // $this->copySpriteData();
    }

    public function getImage(int $width, int $height) {
        $src = imagecreatefrompng(sprintf("Images/%s.png", ucfirst($this->league)));
        $dest = imagecreatetruecolor($width, $height);
        
        $im = imagecreatetruecolor(55, 30);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagecolortransparent($dest, $black);

        imagecopy($dest, $src, 0, 0, 0, 0, $width, $height);        
        imagepng($dest);

        imagedestroy($dest);
        imagedestroy($src);
    }

    /**
     * Once all sprites have been copied, this function should/could be removed
     */
    private function copySpriteData() {
        $url = sprintf("http://us.battle.net/sc2/static/images/icons/league/%s.png", $this->league);
        $data = $this->request->getRawData($url);

        $newImage = fopen(sprintf("Images/%s.png", ucfirst($this->league)), "w") or die("Unable to open file!");
        fwrite($newImage, $data);
        fclose($newImage);
    }
}