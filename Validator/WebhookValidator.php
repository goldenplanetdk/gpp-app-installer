<?php

namespace GoldenPlanet\Gpp\App\Installer\Validator;

class WebhookValidator
{

    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function validate($payload, $hmac)
    {
        if (hash_hmac('sha256', $payload, $this->secret) !== $hmac) {
            throw new \InvalidArgumentException(sprintf('Hmac verification for webhook failed [%s] [%s]', var_export($payload, true), var_export($this->secret, true)));
        }
    }
}
