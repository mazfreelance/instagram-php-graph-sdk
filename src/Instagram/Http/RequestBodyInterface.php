<?php

namespace Maztech\Http;

/**
 * Interface
 *
 * @package Instagram
 */
interface RequestBodyInterface
{
    /**
     * Get the body of the request to send to Graph.
     *
     * @return string
     */
    public function getBody();
}
