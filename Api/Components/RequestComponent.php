<?php

class Request
{

    /**
     * @var cURL
     */
    private $__curlHandler;

    public function __construct()
    {
        $this->__curlHandler = curl_init();
        $this->__setupCurl();
    }

    /**
     * Do a basic GET request on this objects $url field
     * Returns the web response as a string
     *
     * @var string $url
     * @return string
     */
    public function getRawData($url)
    {
        $this->__setUrl($url);
        $response = curl_exec($this->__curlHandler);
        return $response;
    }

    /**
     * Do a basic GET request on this objects $url field
     * Returns the web response as a json object
     *
     * @var string $url
     * @var bool $asArray
     * @return JSON
     */
    public function getJsonData($url, $asArray = false)
    {
        printf("url is: %s", $url);
        $this->__setUrl($url);
        $response     = curl_exec($this->__curlHandler);
        $jsonResponse = json_decode($response, $asArray);
        return $jsonResponse;
    }

    /**
     * Do an authenticated POST request
     *
     * @var string $url
     * @var string $username
     * @var string $password
     * @var string $parameterData
     * @return JSON
     */
    public function postAuthData($url, $username, $password, $parameterData = '', $asArray = false)
    {
        $this->__setUrl($url);
        curl_setopt($this->__curlHandler, CURLOPT_POST, 1);
        curl_setopt($this->__curlHandler, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($this->__curlHandler, CURLOPT_POSTFIELDS, $parameterData);
        $response     = curl_exec($this->__curlHandler);
        $jsonResponse = json_decode($response, $asArray);
        return $jsonResponse;
    }

    /**
     * Close the current instance of the curl handler.
     * If called, then you'd need to create a new Requests object to later call more urls.
     */
    public function closeRequest()
    {
        curl_close($this->__curlHandler);
    }

    /**
     * Set the url to use for the curl handler
     *
     * @var string $url
     */
    private function __setUrl($url)
    {
        curl_setopt($this->__curlHandler, CURLOPT_URL, $url);
    }

    /**
     * Setup any options deemed necessary for curl
     */
    private function __setupCurl()
    {
        curl_setopt($this->__curlHandler, CURLOPT_RETURNTRANSFER, 1);
    }
}
