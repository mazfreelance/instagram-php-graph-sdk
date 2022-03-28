<?php

namespace Maztech;

use Maztech\Authentication\AccessToken;
// use Maztech\Exceptions\InstagramSDKException;

class InstagramApp implements \Serializable
{
    /**
     * @var string The app ID.
     */
    protected $id;

    /**
     * @var string The app secret.
     */
    protected $secret;

    /**
     * @param string $id
     * @param string $secret
     *
     * @throws InstagramSDKException
     */
    public function __construct($id, $secret)
    {
        // if (!is_string($id)
        //   // Keeping this for BC. Integers greater than PHP_INT_MAX will make is_int() return false
        //   && !is_int($id)) {
        //     throw new InstagramSDKException('The "app_id" must be formatted as a string since many app ID\'s are greater than PHP_INT_MAX on some systems.');
        // }
        // We cast as a string in case a valid int was set on a 64-bit system and this is unserialised on a 32-bit system
        $this->id = (string) $id;
        $this->secret = $secret;
    }

    /**
     * Returns the app ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the app secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Returns an app access token.
     *
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return new AccessToken($this->id . '|' . $this->secret);
    }

    /**
     * Serializes the InstagramApp entity as a string.
     *
     * @return string
     */
    public function serialize()
    {
        return implode('|', [$this->id, $this->secret]);
    }

    /**
     * Unserializes a string as a InstagramApp entity.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($id, $secret) = explode('|', $serialized);

        $this->__construct($id, $secret);
    }
}
