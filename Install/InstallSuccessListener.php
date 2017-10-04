<?php

namespace GoldenPlanet\Gpp\App\Installer\Install;

use Doctrine\DBAL\Connection;
use GoldenPlanet\Gpp\App\Installer\Api\StoreApiFactory;
use GoldenPlanet\Gpp\App\Installer\InstallationSuccess;

class InstallSuccessListener
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var StoreApiFactory
     */
    private $api;
    private $backUrl;

    /**
     * InstallSuccessListener constructor.
     * @param Connection $connection
     * @param StoreApiFactory $api
     * @param $backUrl
     */
    public function __construct(Connection $connection, StoreApiFactory $api, $backUrl)
    {
        $this->connection = $connection;
        $this->api = $api;
        $this->backUrl = $backUrl;
    }

    public function onSuccess(InstallationSuccess $event) {
        $data = [
            'shop' => $event->shop(),
            'token' => $event->token(),
            'is_secure_protocol' => (int) ($event->protocol() == 'https'),
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $shopId = $this->connection->executeQuery('SELECT id FROM installations WHERE shop = :shop', ['shop' => $event->shop()])->fetchColumn();

        if ($shopId) {
            $this->connection->update('installations', $data, ['id' => $shopId]);
        } else {
            $this->connection->insert('installations', $data);
        }

        $client = $this->api->createClient($event->shop());

        $data = [
            'url' => $this->backUrl,
            'event_name' => 'app.uninstalled',
        ];
        $client->call('POST', '/api/v1/webhooks', $data);
    }
}
