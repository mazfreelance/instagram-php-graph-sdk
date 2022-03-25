<?php

namespace Instagram\Url;

/**
 * Interface UrlDetectionInterface
 *
 * @package Instagram
 */
interface UrlDetectionInterface
{
    /**
     * Get the currently active URL.
     *
     * @return string
     */
    public function getCurrentUrl();
}
