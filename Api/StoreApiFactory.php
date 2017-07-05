<?php


namespace GoldenPlanet\Gpp\App\Installer\Api;


use Doctrine\DBAL\Connection;
use GoldenPlanet\Gpp\App\Installer\Client;

class StoreApiFactory
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Client
     */
    private $client;

    /**
     * StoreApiFactory constructor.
     * @param Connection $connection
     * @param Client $client
     */
    public function __construct(Connection $connection, Client $client)
    {
        $this->connection = $connection;
        $this->client = $client;
    }

    public function createClient($shop)
    {
        $installations = $this->connection->fetchAll('SELECT token, is_secure_protocol from installations WHERE shop = ? ORDER BY id DESC', [$shop]);

        if (!count($installations)) {
            throw new \Exception(sprintf('Store %s is not found in database', $shop));
        }

        $installation = $installations[0];

        return new StoreApi(
            $shop,
            $installation['is_secure_protocol'],
            $installation['token'],
            $this->client
        );
    }
}
