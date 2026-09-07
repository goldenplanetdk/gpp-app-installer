<?php

namespace GoldenPlanet\Gpp\App\Installer\Validator;

class HmacValidator
{

    /**
     * How old a signed URL may be, in seconds.
     *
     * 300 s is what the OBB backend's own design asks for. The window also has
     * to absorb the clock difference between the shop that signs and the app
     * that verifies, which is why it is applied in both directions.
     */
    const DEFAULT_MAX_AGE = 300;

    private $secret;

    private $maxAge;

    /**
     * @param string $secret the app secret both sides sign with
     * @param int $maxAge seconds a signed URL stays valid once it carries a
     *     timestamp. Widen it for an app whose signed links are rendered into
     *     a page and clicked later: a shop's order page bakes a signed
     *     "direct" app link into the HTML, and the owner may well click it
     *     more than five minutes after the page was drawn.
     */
    public function __construct($secret, $maxAge = self::DEFAULT_MAX_AGE)
    {
        $this->secret = $secret;
        $this->maxAge = $maxAge;
    }

    public function validate($queryString)
    {
        parse_str($queryString, $data);
        $hmac = $data['hmac'] ?? '';

        // validate hmac
        if (!$hmac) {
            throw new \InvalidArgumentException('Invalid hmac value');
        }

        unset($data['hmac']);

        if (hash_hmac('sha256', http_build_query($data), $this->secret) !== $hmac) {
            throw new \InvalidArgumentException('Hmac verification failed');
        }

        $this->validateFreshness($data);
    }

    /**
     * Refuse a signed URL that is too old to be a live request.
     *
     * Without this a signature is valid forever, and the full signed URL -
     * hmac included - lands in the app's ordinary web-server access log on
     * every request. The signature does not cover the request path either, so
     * one captured log line is a permanent, reusable ticket into *any* route
     * of the app that logged it.
     *
     * Checked only when a `timestamp` parameter is present, rather than made
     * mandatory. That is not the weaker rule it looks like: the timestamp is
     * inside the string the hmac was computed over - the signer appends it
     * before signing - so it cannot be dropped or edited without failing the
     * check above, which has already run. A captured URL therefore always
     * carries its own timestamp, and is therefore always checked.
     *
     * The conditional shape is not there to keep a timestamp-less caller
     * working: every signer in the fleet appends one to everything it signs,
     * so no such caller is known to exist and the allowance is hypothetical.
     * It is there because a mandatory rule would refuse a request this package
     * cannot see the signer of, in an app that pins it by commit hash, to buy
     * a strictness the paragraph above shows is already unreachable.
     *
     * This is the Symfony 3 line (sf-3.0), so the failure is an
     * \InvalidArgumentException — what the two checks above it already throw.
     * master raises an HmacValidationException instead; that class does not
     * exist here, and introducing it would change what every caller on this
     * line has to catch. Same rule, same window, the exception type each line
     * already uses.
     *
     * @throws \InvalidArgumentException
     */
    private function validateFreshness(array $data)
    {
        // array_key_exists because presence is the question being asked. It is
        // not a bug fix in disguise: parse_str never yields null - "?timestamp="
        // and a bare "?timestamp" both parse to an empty string - so isset()
        // would agree with it on every input this method can actually receive.
        // The empty string is caught just below, as unusable rather than absent.
        if (!array_key_exists('timestamp', $data)) {
            return;
        }

        $timestamp = $data['timestamp'];

        // A signer that puts something unreadable here has lost the protection
        // rather than opted out of it, so this is a rejection and not a skip.
        // is_scalar first: "?timestamp[]=1" parses to an array, and is_numeric()
        // on an array is a fatal TypeError on PHP 8.
        if (!is_scalar($timestamp) || !is_numeric($timestamp)) {
            throw new \InvalidArgumentException('Invalid timestamp value');
        }

        // Both directions. A clock ahead of ours is as much a sign of a
        // misconfigured signer as one behind it, and accepting the future half
        // would let a signer mint URLs that stay valid for as long as it likes.
        if (abs(time() - (int)$timestamp) > $this->maxAge) {
            throw new \InvalidArgumentException('Signature has expired');
        }
    }
}
