<?php


/**
 * @see https://github.com/sarciszewski/php-future/blob/master/src/Security.php#L37-L51
 */
if (!function_exists('hash_equals')) {
    function hash_equals($knownString, $userString)
    {
        if (function_exists('mb_strlen')) {
            $kLen = mb_strlen($knownString, '8bit');
            $uLen = mb_strlen($userString, '8bit');
        } else {
            $kLen = strlen($knownString);
            $uLen = strlen($userString);
        }
        if ($kLen !== $uLen) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < $kLen; $i++) {
            $result |= (ord($knownString[$i]) ^ ord($userString[$i]));
        }

        // They are only identical strings if $result is exactly 0...
        return 0 === $result;
    }
}
