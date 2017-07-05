<?php


namespace GoldenPlanet\Gpp\App\Installer;


use Symfony\Component\EventDispatcher\Event;

class InstallationSuccess extends Event
{

    private $shop;
    private $token;
    private $protocol;

    public function __construct($shop, $token, $protocol)
    {
        $this->shop = $shop;
        $this->token = $token;
        $this->protocol = $protocol;
    }

    /**
     * @return mixed
     */
    public function shop()
    {
        return $this->shop;
    }

    /**
     * @return mixed
     */
    public function token()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function protocol()
    {
        return $this->protocol;
    }
}
