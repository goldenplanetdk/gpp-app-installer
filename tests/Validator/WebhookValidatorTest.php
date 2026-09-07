<?php

namespace GoldenPlanet\Gpp\App\Installer\Tests\Validator;

use GoldenPlanet\Gpp\App\Installer\Validator\WebhookValidator;
use PHPUnit\Framework\TestCase;

class WebhookValidatorTest extends TestCase
{

    const SECRET = 'f8a1c0d3e2b5947608a1c0d3e2b5947608a1c0d3e2b5947608a1c0d3e2b59476';

    public function testAcceptsACorrectlySignedPayload()
    {
        $payload = '{"shop":"shop.dk"}';

        (new WebhookValidator(self::SECRET))->validate(
            $payload,
            hash_hmac('sha256', $payload, self::SECRET)
        );

        $this->addToAssertionCount(1);
    }

    public function testRejectsAWrongSignature()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new WebhookValidator(self::SECRET))->validate('{"shop":"shop.dk"}', 'not-the-signature');
    }

    /**
     * The refusal used to var_export() the secret and the payload into its own
     * message, and every app that embeds this package logs exception messages -
     * to the error log, and on to Sentry. One unauthenticated request with a
     * bogus X-OBB-Signature therefore wrote the secret that signs every
     * handshake into a log, and a leaked secret mints valid signatures forever.
     */
    public function testTheRefusalNeverQuotesTheSecretOrThePayload()
    {
        try {
            (new WebhookValidator(self::SECRET))->validate('{"card":"4111111111111111"}', 'wrong');
            $this->fail('A wrong signature must be refused');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringNotContainsString(self::SECRET, $e->getMessage());
            $this->assertStringNotContainsString('4111111111111111', $e->getMessage());
        }
    }
}
