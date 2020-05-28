<?php

namespace GoldenPlanet\Gpp\App\Installer\Validator;

use GoldenPlanet\Gpp\App\Installer\Exception\HmacValidationException;

class HmacValidator
{

    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function validate($queryString)
    {
        parse_str($queryString, $data);
        $hmac = $data['hmac'] ?? '';

        // validate hmac
        if (!$hmac) {
            throw new HmacValidationException('Invalid hmac value');
        }

        unset($data['hmac']);

        if (hash_hmac('sha256', http_build_query($data), $this->secret) !== $hmac) {
            throw new HmacValidationException('Hmac verification failed');
        }
    }
}
