<?php

namespace GoldenPlanet\Gpp\App\Installer\Validator;

class WebhookValidator
{

    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * The failure says nothing about what was compared.
     *
     * It used to var_export() the app secret and the whole payload into the
     * message. Every consumer of this package logs exception messages
     * somewhere - the Silex apps put them in the error log and on to Sentry -
     * so any unauthenticated request with a bogus X-OBB-Signature was enough
     * to write the secret that signs every handshake into a log an attacker
     * may be able to read. A leaked secret mints valid signatures forever,
     * which is what makes it worse than the request it was refusing.
     */
    public function validate($payload, $hmac)
    {
        if (hash_hmac('sha256', $payload, $this->secret) !== $hmac) {
            throw new \InvalidArgumentException('Hmac verification for webhook failed');
        }
    }
}
