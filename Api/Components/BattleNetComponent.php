<?php

class BattleNet
{

    /**
     * @var array
     */
    private $configData;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     */
    private $redirectUrl;

    /**
     * @var string
     */
    private $authorizeCode;

    /**
     * @var string
     */
    private $state;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $baseUrl = 'https://us.battle.net/oauth';

    public function __construct($configName, $code, $state)
    {
        $this->configData = parse_ini_file($configName, TRUE);

        $this->authorizeCode = $code;
        $this->state = $state;

        $this->key = $this->configData['battlenet_api']['client_id'];
        $this->secret = $this->configData['battlenet_api']['client_secret'];
        $this->redirectUrl = $this->configData['battlenet_api']['redirect_url'];

        $this->request = new Request();
    }

    public function getNewAccessCode()
    {
        $data = $this->authorizeRoute();

        if (property_exists($data, 'access_token')) {
            $code = $data->{'access_token'};
            echo $code . " valid for 1 month";
        }
        else {
            echo "Couldn't retrieve code. Please ensure correct configuration.";
        }
    }

    private function authorizeRoute()
    {
        $codeUrl = sprintf(
            "%s/token?client_id=%s&client_secret=%s&redirect_uri=%s&scope=sc2.profile&grant_type=authorization_code&code=%s", 
            $this->baseUrl,
            $this->key,
            $this->secret,
            $this->redirectUrl,
            $this->authorizeCode
        );

        $data = $this->request->getJsonData($codeUrl);

        return $data;
    }
}

