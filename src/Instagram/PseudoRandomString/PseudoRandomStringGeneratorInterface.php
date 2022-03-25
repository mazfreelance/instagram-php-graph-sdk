<?php

namespace Instagram\PseudoRandomString;

/**
 * Interface
 *
 * @package Instagram
 */
interface PseudoRandomStringGeneratorInterface
{
    /**
     * Get a cryptographically secure pseudo-random string of arbitrary length.
     *
     * @see http://sockpuppet.org/blog/2014/02/25/safely-generate-random-numbers/
     *
     * @param int $length The length of the string to return.
     *
     * @return string
     *
     * @throws \Instagram\Exceptions\InstagramSDKException|\InvalidArgumentException
     */
    public function getPseudoRandomString($length);
}
