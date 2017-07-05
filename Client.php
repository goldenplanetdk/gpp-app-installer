<?php

namespace GoldenPlanet\Gpp\App\Installer;

interface Client
{
    public function request($method, $url, $options);
}
