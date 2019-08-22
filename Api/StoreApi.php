<?php

namespace GoldenPlanet\Gpp\App\Installer\Api;

use GoldenPlanet\Gpp\App\Installer\Client;

class StoreApi
{
    private $domain;
    private $https;
    private $token;

    /**
     * @var Client
     */
    private $client;

    /**
     * StoreApi constructor.
     * @param $domain
     * @param $https
     * @param $token
     * @param Client $client
     */
    public function __construct(string $domain, bool $https, $token, Client $client)
    {
        $this->domain = $domain;
        $this->https = $https;
        $this->token = $token;
        $this->client = $client;
    }

    public function call($method, $path, $params = array())
    {
        $baseUrl = ($this->https ? 'https' : 'http') . "://{$this->domain}/";

        $url = $baseUrl . ltrim($path, '/');
        $query = in_array($method, array('GET', 'DELETE')) ? $params : array();
        $payload = in_array($method, array('POST', 'PUT')) ? json_encode($params) : array();
        $headers = in_array($method, array('POST', 'PUT')) ? array("Content-Type: application/json; charset=utf-8", 'Expect:') : array();

        // add auth headers
        $headers[] = 'Authorization: Bearer ' . $this->token;

        $options = [
            'query' => $query,
            'form_params' => $payload,
            'headers' => $headers,
        ];
        $response = $this->client->request($method, $url, $options);

        $response = preg_replace('/[^(\x00-\x7F)]*/','', $response);
        $response = json_decode($response, true);
        if (isset($response['errors'])) {
            throw new \DomainException("Bad request. $method - $path");
        }
        return $response;
    }

}
