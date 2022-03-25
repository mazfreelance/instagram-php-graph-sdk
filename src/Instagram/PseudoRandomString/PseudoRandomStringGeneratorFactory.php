<?php

namespace Instagram\PseudoRandomString;

use Instagram\Exceptions\InstagramSDKException;
use InvalidArgumentException;

class PseudoRandomStringGeneratorFactory
{
    private function __construct()
    {
        // a factory constructor should never be invoked
    }

    /**
     * Pseudo random string generator creation.
     *
     * @param PseudoRandomStringGeneratorInterface|string|null $generator
     *
     * @throws InvalidArgumentException If the pseudo random string generator must be set to "random_bytes", "mcrypt", "openssl", or "urandom", or be an instance of Instagram\PseudoRandomString\PseudoRandomStringGeneratorInterface.
     *
     * @return PseudoRandomStringGeneratorInterface
     */
    public static function createPseudoRandomStringGenerator($generator)
    {
        if (!$generator) {
            return self::detectDefaultPseudoRandomStringGenerator();
        }

        if ($generator instanceof PseudoRandomStringGeneratorInterface) {
            return $generator;
        }

        if ('random_bytes' === $generator) {
            return new RandomBytesPseudoRandomStringGenerator();
        }
        if ('mcrypt' === $generator) {
            return new McryptPseudoRandomStringGenerator();
        }
        if ('openssl' === $generator) {
            return new OpenSslPseudoRandomStringGenerator();
        }
        if ('urandom' === $generator) {
            return new UrandomPseudoRandomStringGenerator();
        }

        throw new InvalidArgumentException('The pseudo random string generator must be set to "random_bytes", "mcrypt", "openssl", or "urandom", or be an instance of Instagram\PseudoRandomString\PseudoRandomStringGeneratorInterface');
    }

    /**
     * Detects which pseudo-random string generator to use.
     *
     * @throws InstagramSDKException If unable to detect a cryptographically secure pseudo-random string generator.
     *
     * @return PseudoRandomStringGeneratorInterface
     */
    private static function detectDefaultPseudoRandomStringGenerator()
    {
        // Check for PHP 7's CSPRNG first to keep mcrypt deprecation messages from appearing in PHP 7.1.
        if (function_exists('random_bytes')) {
            return new RandomBytesPseudoRandomStringGenerator();
        }

        // Since openssl_random_pseudo_bytes() can sometimes return non-cryptographically
        // secure pseudo-random strings (in rare cases), we check for mcrypt_create_iv() next.
        if (function_exists('mcrypt_create_iv')) {
            return new McryptPseudoRandomStringGenerator();
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            return new OpenSslPseudoRandomStringGenerator();
        }

        if (!ini_get('open_basedir') && is_readable('/dev/urandom')) {
            return new UrandomPseudoRandomStringGenerator();
        }

        throw new InstagramSDKException('Unable to detect a cryptographically secure pseudo-random string generator.');
    }
}
