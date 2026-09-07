<?php

namespace GoldenPlanet\Gpp\App\Installer\Tests\Validator;

use GoldenPlanet\Gpp\App\Installer\Exception\HmacValidationException;
use GoldenPlanet\Gpp\App\Installer\Validator\HmacValidator;
use PHPUnit\Framework\TestCase;

class HmacValidatorTest extends TestCase
{

    const SECRET = 'f8a1c0d3e2b5947608a1c0d3e2b5947608a1c0d3e2b5947608a1c0d3e2b59476';

    /**
     * Signs exactly the way the shop does.
     *
     * Copied deliberately rather than shared with the validator: this is the
     * other side of the wire, and a helper both sides used would agree with
     * itself even if both were wrong. It mirrors the shop's
     * GoldenPlanet\AppStore\Model\App::signQueryString(), which appends
     * `timestamp` last and then the `hmac` after it.
     */
    private function sign(array $params)
    {
        $queryString = http_build_query($params);
        $params['hmac'] = hash_hmac('sha256', $queryString, self::SECRET);

        return http_build_query($params);
    }

    private function validator($maxAge = null)
    {
        return $maxAge === null
            ? new HmacValidator(self::SECRET)
            : new HmacValidator(self::SECRET, $maxAge);
    }

    public function testAcceptsAFreshlySignedQueryString()
    {
        $query = $this->sign(['shop' => 'shop.dk', 'timestamp' => time()]);

        $this->validator()->validate($query);

        $this->addToAssertionCount(1);
    }

    public function testAcceptsASignedQueryStringThatCarriesNoTimestamp()
    {
        // The rule is conditional, so this is what "conditional" means. It is
        // not a caller anyone has: every signer in the fleet appends a
        // timestamp to everything it signs, so this shape is hypothetical - the
        // conditional exists because it cannot be evaded, not because someone
        // is relying on it.
        $query = $this->sign(['shop' => 'shop.dk']);

        $this->validator()->validate($query);

        $this->addToAssertionCount(1);
    }

    public function testRejectsAMissingHmac()
    {
        $this->expectException(HmacValidationException::class);

        $this->validator()->validate('shop=shop.dk&timestamp=' . time());
    }

    public function testRejectsATamperedQueryString()
    {
        $query = $this->sign(['shop' => 'shop.dk', 'timestamp' => time()]);

        $this->expectException(HmacValidationException::class);

        $this->validator()->validate(str_replace('shop.dk', 'evil.dk', $query));
    }

    /**
     * The point of the whole change.
     *
     * The signed URL, hmac and all, is in the app's web-server access log, and
     * the signature covers no path - so one old log line was a permanent
     * trigger for any route of the app, the GLS credential push included.
     */
    public function testRejectsASignatureOlderThanTheWindow()
    {
        $query = $this->sign(['shop' => 'shop.dk', 'timestamp' => time() - 301]);

        $this->expectException(HmacValidationException::class);
        $this->expectExceptionMessage('Signature has expired');

        $this->validator()->validate($query);
    }

    public function testAcceptsASignatureAtTheEdgeOfTheWindow()
    {
        $query = $this->sign(['shop' => 'shop.dk', 'timestamp' => time() - 300]);

        $this->validator()->validate($query);

        $this->addToAssertionCount(1);
    }

    /**
     * A clock ahead of ours is as much a misconfiguration as one behind it,
     * and accepting the future half would let a signer mint URLs that stay
     * valid for as long as it cared to.
     */
    public function testRejectsASignatureDatedInTheFuture()
    {
        $query = $this->sign(['shop' => 'shop.dk', 'timestamp' => time() + 301]);

        $this->expectException(HmacValidationException::class);
        $this->expectExceptionMessage('Signature has expired');

        $this->validator()->validate($query);
    }

    public function testTheWindowIsAConstructorParameter()
    {
        $query = $this->sign(['shop' => 'shop.dk', 'timestamp' => time() - 3600]);

        // Refused by the default window ...
        try {
            $this->validator()->validate($query);
            $this->fail('An hour-old signature must be refused by the default window');
        } catch (HmacValidationException $e) {
            $this->addToAssertionCount(1);
        }

        // ... and accepted by a widened one, which is how an app whose signed
        // links are rendered into a page and clicked later opts out.
        $this->validator(7200)->validate($query);

        $this->addToAssertionCount(1);
    }

    /**
     * "?timestamp=" is present but unusable. A reader that treats "no usable
     * value" as "no timestamp" would skip the freshness check on it, which is
     * the one shape that must never be skipped - it is inside the signature,
     * so a signer that produced it has lost the protection, not declined it.
     */
    public function testRejectsAnEmptyTimestamp()
    {
        $query = $this->sign(['shop' => 'shop.dk', 'timestamp' => '']);

        $this->expectException(HmacValidationException::class);
        $this->expectExceptionMessage('Invalid timestamp value');

        $this->validator()->validate($query);
    }

    public function testRejectsANonNumericTimestamp()
    {
        $query = $this->sign(['shop' => 'shop.dk', 'timestamp' => 'yesterday']);

        $this->expectException(HmacValidationException::class);
        $this->expectExceptionMessage('Invalid timestamp value');

        $this->validator()->validate($query);
    }

    /**
     * parse_str turns "timestamp[]=1" into an array, and is_numeric() on an
     * array is a fatal TypeError on PHP 8 - a signed request must not be able
     * to crash the verifier.
     */
    public function testRejectsAnArrayTimestampWithoutCrashing()
    {
        $query = $this->sign(['shop' => 'shop.dk', 'timestamp' => [time()]]);

        $this->expectException(HmacValidationException::class);
        $this->expectExceptionMessage('Invalid timestamp value');

        $this->validator()->validate($query);
    }

    /**
     * The freshness check runs after the signature, never instead of it: a
     * fresh timestamp on an unsigned URL is worth nothing.
     */
    public function testAFreshTimestampDoesNotExcuseABadSignature()
    {
        $this->expectException(HmacValidationException::class);
        $this->expectExceptionMessage('Hmac verification failed');

        $this->validator()->validate('shop=shop.dk&timestamp=' . time() . '&hmac=' . str_repeat('0', 64));
    }
}
