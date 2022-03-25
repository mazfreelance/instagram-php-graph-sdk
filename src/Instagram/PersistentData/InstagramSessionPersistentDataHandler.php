<?php

namespace Instagram\PersistentData;

use Instagram\Exceptions\InstagramSDKException;

/**
 * Class InstagramSessionPersistentDataHandler
 *
 * @package Instagram
 */
class InstagramSessionPersistentDataHandler implements PersistentDataInterface
{
    /**
     * @var string Prefix to use for session variables.
     */
    protected $sessionPrefix = 'FBRLH_';

    /**
     * Init the session handler.
     *
     * @param boolean $enableSessionCheck
     *
     * @throws InstagramSDKException
     */
    public function __construct($enableSessionCheck = true)
    {
        if ($enableSessionCheck && session_status() !== PHP_SESSION_ACTIVE) {
            throw new InstagramSDKException(
                'Sessions are not active. Please make sure session_start() is at the top of your script.',
                720
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        if (isset($_SESSION[$this->sessionPrefix . $key])) {
            return $_SESSION[$this->sessionPrefix . $key];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value)
    {
        $_SESSION[$this->sessionPrefix . $key] = $value;
    }
}
