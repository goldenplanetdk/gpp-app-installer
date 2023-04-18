<?php

namespace GoldenPlanet\Gpp\App\Installer;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * {@inheritDoc}
 */
final class UninstalledSuccess extends Event
{

    private $alias;
    private $domain;
    private $name;

    public function __construct($data)
    {
        $default = [
            'domain' => '',
            'name' => '',
            'alias' => '',
        ];
        $data = array_merge($default, $data);
        $this->alias = $data['alias'];
        $this->name = $data['name'];
        $this->domain = $data['domain'];
    }

    /**
     * @return mixed
     */
    public function alias()
    {
        return $this->alias;
    }

    /**
     * @return mixed
     */
    public function domain()
    {
        return $this->domain;
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->name;
    }
}
