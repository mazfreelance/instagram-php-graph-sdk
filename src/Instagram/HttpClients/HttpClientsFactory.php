<?php

namespace Instagram\HttpClients;

use GuzzleHttp\Client;
use InvalidArgumentException;
use Exception;

class HttpClientsFactory
{
    private function __construct()
    {
        // a factory constructor should never be invoked
    }

    /**
     * HTTP client generation.
     *
     * @param InstagramHttpClientInterface|Client|string|null $handler
     *
     * @throws Exception                If the cURL extension or the Guzzle client aren't available (if required).
     * @throws InvalidArgumentException If the http client handler isn't "curl", "stream", "guzzle", or an instance of Instagram\HttpClients\InstagramHttpClientInterface.
     *
     * @return InstagramHttpClientInterface
     */
    public static function createHttpClient($handler)
    {
        if (!$handler) {
            return self::detectDefaultClient();
        }

        if ($handler instanceof InstagramHttpClientInterface) {
            return $handler;
        }

        if ('stream' === $handler) {
            return new InstagramStreamHttpClient();
        }
        if ('curl' === $handler) {
            if (!extension_loaded('curl')) {
                throw new Exception('The cURL extension must be loaded in order to use the "curl" handler.');
            }

            return new InstagramCurlHttpClient();
        }

        if ('guzzle' === $handler && !class_exists('GuzzleHttp\Client')) {
            throw new Exception('The Guzzle HTTP client must be included in order to use the "guzzle" handler.');
        }

        if ($handler instanceof Client) {
            return new InstagramGuzzleHttpClient($handler);
        }
        if ('guzzle' === $handler) {
            return new InstagramGuzzleHttpClient();
        }

        throw new InvalidArgumentException('The http client handler must be set to "curl", "stream", "guzzle", be an instance of GuzzleHttp\Client or an instance of Instagram\HttpClients\InstagramHttpClientInterface');
    }

    /**
     * Detect default HTTP client.
     *
     * @return InstagramHttpClientInterface
     */
    private static function detectDefaultClient()
    {
        if (extension_loaded('curl')) {
            return new InstagramCurlHttpClient();
        }

        if (class_exists('GuzzleHttp\Client')) {
            return new InstagramGuzzleHttpClient();
        }

        return new InstagramStreamHttpClient();
    }
}
