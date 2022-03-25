<?php

namespace Instagram;

use Instagram\HttpClients\InstagramCurlHttpClient;
use Instagram\HttpClients\InstagramHttpClientInterface;
use Instagram\HttpClients\InstagramStreamHttpClient;
use Instagram\Exceptions\InstagramSDKException;

/**
 * Class InstagramClient
 *
 * @package Instagram
 */
class InstagramClient
{
    /**
     * @const string Production Graph API URL.
     */
    const BASE_GRAPH_URL = 'https://graph.instagram.com';

    /**
     * @const string The base authorization URL.
     */
    const BASE_AUTHORIZATION_URL = 'https://api.instagram.com';

    /**
     * @const int The timeout in seconds for a normal request.
     */
    const DEFAULT_REQUEST_TIMEOUT = 60;

    /**
     * @const int The timeout in seconds for a request that contains file uploads.
     */
    const DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT = 3600;

    /**
     * @const int The timeout in seconds for a request that contains video uploads.
     */
    const DEFAULT_VIDEO_UPLOAD_REQUEST_TIMEOUT = 7200;

    /**
     * @var InstagramHttpClientInterface HTTP client handler.
     */
    protected $httpClientHandler;

    /**
     * @var int The number of calls that have been made to Graph.
     */
    public static $requestCount = 0;

    /**
     * Instantiates a new InstagramClient object.
     *
     * @param InstagramHttpClientInterface|null $httpClientHandler
     * @param boolean                          $enableBeta
     */
    public function __construct(InstagramHttpClientInterface $httpClientHandler = null)
    {
        $this->httpClientHandler = $httpClientHandler ?: $this->detectHttpClientHandler();
    }

    /**
     * Sets the HTTP client handler.
     *
     * @param InstagramHttpClientInterface $httpClientHandler
     */
    public function setHttpClientHandler(InstagramHttpClientInterface $httpClientHandler)
    {
        $this->httpClientHandler = $httpClientHandler;
    }

    /**
     * Returns the HTTP client handler.
     *
     * @return InstagramHttpClientInterface
     */
    public function getHttpClientHandler()
    {
        return $this->httpClientHandler;
    }

    /**
     * Detects which HTTP client handler to use.
     *
     * @return InstagramHttpClientInterface
     */
    public function detectHttpClientHandler()
    {
        return extension_loaded('curl') ? new InstagramCurlHttpClient() : new InstagramStreamHttpClient();
    }

    /**
     * Returns the base Graph URL.
     *
     * @return string
     */
    public function getBaseGraphUrl()
    {
        return static::BASE_GRAPH_URL;
    }

    /**
     * Returns the base Authorization URL.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return static::BASE_AUTHORIZATION_URL;
    }

    /**
     * Prepares the request for sending to the client handler.
     *
     * @param InstagramRequest $request
     * @param string $baseUrl value=graph|authorization
     *
     * @return array
     */
    public function prepareRequestMessage(InstagramRequest $request, $baseUrl = 'graph')
    {
        if($baseUrl == 'authorization') {
            $url = $this->getBaseAuthorizationUrl();
        } else {
            $url = $this->getBaseGraphUrl();
        } 
        
        $url .=  $request->getUrl();

        $requestBody = $request->getUrlEncodedBody();
        $request->setHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        return [
            $url,
            $request->getMethod(),
            $request->getHeaders(),
            $requestBody->getBody(),
        ];
    }

    /**
     * Makes the request to Graph and returns the result.
     *
     * @param InstagramRequest $request
     * @param string $baseUrl value=graph|authorization
     *
     * @return InstagramResponse
     *
     * @throws InstagramSDKException
     */
    public function sendRequest(InstagramRequest $request, $baseUrl = 'graph')
    {
        if (get_class($request) === 'Instagram\InstagramRequest') {
            $request->validateAccessToken();
        }

        list($url, $method, $headers, $body) = $this->prepareRequestMessage($request, $baseUrl);

        // dd($request, $url, $method, $headers, $body);
        // Since file uploads can take a while, we need to give more time for uploads
        $timeOut = static::DEFAULT_REQUEST_TIMEOUT;

        // Should throw `InstagramSDKException` exception on HTTP client error.
        // Don't catch to allow it to bubble up.
        $rawResponse = $this->httpClientHandler->send($url, $method, $body, $headers, $timeOut);
        
        static::$requestCount++;

        $returnResponse = new InstagramResponse(
            $request,
            $rawResponse->getBody(),
            $rawResponse->getHttpResponseCode(),
            $rawResponse->getHeaders()
        );

        if ($returnResponse->isError()) {
            throw $returnResponse->getThrownException();
        }

        return $returnResponse;
    }

    /**
     * Makes a batched request to Graph and returns the result.
     *
     * @param InstagramBatchRequest $request
     *
     * @return InstagramBatchResponse
     *
     * @throws InstagramSDKException
     */
    public function sendBatchRequest(InstagramBatchRequest $request)
    {
        $request->prepareRequestsForBatch();
        $response = $this->sendRequest($request);

        return new InstagramBatchResponse($request, $response);
    }
}
