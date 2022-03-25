<?php

namespace Instagram\PseudoRandomString;

trait PseudoRandomStringGeneratorTrait
{
    /**
     * Validates the length argument of a random string.
     *
     * @param int $length The length to validate.
     *
     * @throws \InvalidArgumentException
     */
    public function validateLength($length)
    {
        if (!is_int($length)) {
            throw new \InvalidArgumentException('getPseudoRandomString() expects an integer for the string length');
        }

        if ($length < 1) {
            throw new \InvalidArgumentException('getPseudoRandomString() expects a length greater than 1');
        }
    }

    /**
     * Converts binary data to hexadecimal of arbitrary length.
     *
     * @param string $binaryData The binary data to convert to hex.
     * @param int    $length     The length of the string to return.
     *
     * @return string
     */
    public function binToHex($binaryData, $length)
    {
        return \substr(\bin2hex($binaryData), 0, $length);
    }
}
