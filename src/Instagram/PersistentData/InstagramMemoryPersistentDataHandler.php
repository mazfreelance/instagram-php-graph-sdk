<?php

namespace Instagram\PersistentData;

/**
 * Class InstagramMemoryPersistentDataHandler
 *
 * @package Instagram
 */
class InstagramMemoryPersistentDataHandler implements PersistentDataInterface
{
    /**
     * @var array The session data to keep in memory.
     */
    protected $sessionData = [];

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        return isset($this->sessionData[$key]) ? $this->sessionData[$key] : null;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value)
    {
        $this->sessionData[$key] = $value;
    }
}
