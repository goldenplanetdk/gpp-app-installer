<?php


namespace GoldenPlanet\Gpp\App\Installer;


use Symfony\Contracts\EventDispatcher\Event;

class UpdateScheme extends Event
{

    private $shop;
    private $isSecure;

    public function __construct($shop, $isSecure)
    {
        $this->shop = $shop;
        $this->isSecure = $isSecure;
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
    public function isSecure()
    {
        return (bool) $this->isSecure;
    }
}
