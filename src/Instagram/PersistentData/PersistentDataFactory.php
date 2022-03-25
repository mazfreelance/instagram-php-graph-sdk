<?php

namespace Instagram\PersistentData;

use InvalidArgumentException;

class PersistentDataFactory
{
    private function __construct()
    {
        // a factory constructor should never be invoked
    }

    /**
     * PersistentData generation.
     *
     * @param PersistentDataInterface|string|null $handler
     *
     * @throws InvalidArgumentException If the persistent data handler isn't "session", "memory", or an instance of Instagram\PersistentData\PersistentDataInterface.
     *
     * @return PersistentDataInterface
     */
    public static function createPersistentDataHandler($handler)
    {
        if (!$handler) {
            return session_status() === PHP_SESSION_ACTIVE
                ? new InstagramSessionPersistentDataHandler()
                : new InstagramMemoryPersistentDataHandler();
        }

        if ($handler instanceof PersistentDataInterface) {
            return $handler;
        }

        if ('session' === $handler) {
            return new InstagramSessionPersistentDataHandler();
        }
        if ('memory' === $handler) {
            return new InstagramMemoryPersistentDataHandler();
        }

        throw new InvalidArgumentException('The persistent data handler must be set to "session", "memory", or be an instance of Instagram\PersistentData\PersistentDataInterface');
    }
}
