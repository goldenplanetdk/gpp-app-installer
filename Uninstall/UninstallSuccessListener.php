<?php

namespace GoldenPlanet\Gpp\App\Installer\Uninstall;

use Doctrine\DBAL\Connection;
use GoldenPlanet\Gpp\App\Installer\UninstalledSuccess;

class UninstallSuccessListener
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * InstallSuccessListener constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function onSuccess(UninstalledSuccess $event) {
        $this->connection->delete('installations', ['shop' => $event->domain()]);
    }
}
