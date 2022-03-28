<?php

namespace Maztech\PseudoRandomString;

use Maztech\Exceptions\InstagramSDKException;

class OpenSslPseudoRandomStringGenerator implements PseudoRandomStringGeneratorInterface
{
    use PseudoRandomStringGeneratorTrait;

    /**
     * @const string The error message when generating the string fails.
     */
    const ERROR_MESSAGE = 'Unable to generate a cryptographically secure pseudo-random string from openssl_random_pseudo_bytes().';

    /**
     * @throws InstagramSDKException
     */
    public function __construct()
    {
        if (!function_exists('openssl_random_pseudo_bytes')) {
            throw new InstagramSDKException(static::ERROR_MESSAGE . 'The function openssl_random_pseudo_bytes() does not exist.');
        }
    }

    /**
     * @inheritdoc
     */
    public function getPseudoRandomString($length)
    {
        $this->validateLength($length);

        $wasCryptographicallyStrong = false;
        $binaryString = openssl_random_pseudo_bytes($length, $wasCryptographicallyStrong);

        if ($binaryString === false) {
            throw new InstagramSDKException(static::ERROR_MESSAGE . 'openssl_random_pseudo_bytes() returned an unknown error.');
        }

        if ($wasCryptographicallyStrong !== true) {
            throw new InstagramSDKException(static::ERROR_MESSAGE . 'openssl_random_pseudo_bytes() returned a pseudo-random string but it was not cryptographically secure and cannot be used.');
        }

        return $this->binToHex($binaryString, $length);
    }
}
