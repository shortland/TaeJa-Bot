<?php

class LeagueImages {

    private $configData;

    private $request;

    private $leagues = ['bronze', 'silver', 'gold', 'platinum', 'diamond', 'master', 'grandmaster'];

    private $league;

    private $tier;

    private $sprite;

    public function __construct(
        string $configName,
        string $league,
        int $tier
    ) {
        $this->configData = parse_ini_file($configName, TRUE);
        $this->request = new Request();
        $this->league = strtolower($league);
        $this->tier = $tier;
        $this->validate();
    }

    public function getImage(int $width, int $height) {
        $src = imagecreatefrompng(sprintf("Static/Images/%s.png", ucfirst($this->league)));
        $dest = imagecreatetruecolor($width, $height);
        
        header("Content-type: image/png");

        $im = imagecreatetruecolor(55, 30);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagecolortransparent($dest, $black);

        imagecopy($dest, $src, 0, 0, 0, 315, $width, $height);        
        imagepng($dest);

        imagedestroy($dest);
        imagedestroy($src);
    }

    private function validate() {
        if (!in_array($this->league, $this->leagues)) {
            printf("Unable to find '%s'. Not a league.\n", $this->league);
            exit();
        }
    }

    /**
     * Once all sprites have been copied, this function should/could be removed
     */
    private function copySpriteData() {
        $url = sprintf("http://us.battle.net/sc2/static/images/icons/league/%s.png", $this->league);
        $data = $this->request->getRawData($url);

        $newImage = fopen(sprintf("Static/Images/%s.png", ucfirst($this->league)), "w") or die("Unable to open file!");
        fwrite($newImage, $data);
        fclose($newImage);
    }
}