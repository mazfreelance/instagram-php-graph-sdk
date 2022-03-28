<?php
/**
 * Copyright 2017 Instagram, Inc.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Instagram.
 *
 * As with any software that integrates with the Instagram platform, your use
 * of this software is subject to the Instagram Developer Principles and
 * Policies [http://developers.instagram.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
namespace Maztech;

use Maztech\Authentication\AccessToken;
use Maztech\Authentication\OAuth2Client;
use Maztech\Exceptions\InstagramSDKException;
use Maztech\GraphNodes\GraphEdge;
use Maztech\Helpers\InstagramRedirectLoginHelper;
use Maztech\HttpClients\HttpClientsFactory;
use Maztech\PersistentData\PersistentDataFactory;
use Maztech\PseudoRandomString\PseudoRandomStringGeneratorFactory;
use Maztech\Url\InstagramUrlDetectionHandler;
use Maztech\Url\UrlDetectionInterface;

/**
 * Class Instagram
 *
 * @package Instagram
 */
class Instagram
{
    /**
     * @const string Version number of the Instagram PHP SDK.
     */
    const VERSION = '1.0.0';

    /**
     * @const string Default Graph API version for requests.
     */
    const DEFAULT_GRAPH_VERSION = 'v8.0';

    /**
     * @const string The name of the environment variable that contains the app ID.
     */
    const APP_ID_ENV_NAME = 'INSTAGRAM_APP_ID';

    /**
     * @const string The name of the environment variable that contains the app secret.
     */
    const APP_SECRET_ENV_NAME = 'INSTAGRAM_APP_SECRET';

    /**
     * @var InstagramApp The InstagramApp entity.
     */
    protected $app;

    /**
     * @var InstagramClient The Instagram client service.
     */
    protected $client;

    /**
     * @var OAuth2Client The OAuth 2.0 client service.
     */
    protected $oAuth2Client;

    /**
     * @var UrlDetectionInterface|null The URL detection handler.
     */
    protected $urlDetectionHandler;

    /**
     * @var PseudoRandomStringGeneratorInterface|null The cryptographically secure pseudo-random string generator.
     */
    protected $pseudoRandomStringGenerator;

    /**
     * @var AccessToken|null The default access token to use with requests.
     */
    protected $defaultAccessToken;

    /**
     * @var string|null The default Graph version we want to use.
     */
    protected $defaultGraphVersion;

    /**
     * @var PersistentDataInterface|null The persistent data handler.
     */
    protected $persistentDataHandler;

    /**
     * @var InstagramResponse|InstagramBatchResponse|null Stores the last request made to Graph.
     */
    protected $lastResponse;

    /**
     * Instantiates a new Instagram super-class object.
     *
     * @param array $config
     *
     * @throws InstagramSDKException
     */
    public function __construct(array $config = [])
    {
        $config = array_merge([
            'app_id' => getenv(static::APP_ID_ENV_NAME),
            'app_secret' => getenv(static::APP_SECRET_ENV_NAME),
            'http_client_handler' => null,
            'persistent_data_handler' => null,
            'pseudo_random_string_generator' => null,
            'url_detection_handler' => null,
        ], $config);

        if (!$config['app_id']) {
            throw new InstagramSDKException('Required "app_id" key not supplied in config and could not find fallback environment variable "' . static::APP_ID_ENV_NAME . '"');
        }
        if (!$config['app_secret']) {
            throw new InstagramSDKException('Required "app_secret" key not supplied in config and could not find fallback environment variable "' . static::APP_SECRET_ENV_NAME . '"');
        }

        $this->app = new InstagramApp($config['app_id'], $config['app_secret']);
        $this->client = new InstagramClient(
            HttpClientsFactory::createHttpClient($config['http_client_handler'])
        );
        $this->pseudoRandomStringGenerator = PseudoRandomStringGeneratorFactory::createPseudoRandomStringGenerator(
            $config['pseudo_random_string_generator']
        );
        $this->setUrlDetectionHandler($config['url_detection_handler'] ?: new InstagramUrlDetectionHandler());
        $this->persistentDataHandler = PersistentDataFactory::createPersistentDataHandler(
            $config['persistent_data_handler']
        );

        if (isset($config['default_access_token'])) {
            $this->setDefaultAccessToken($config['default_access_token']);
        }
    }

    /**
     * Returns the InstagramApp entity.
     *
     * @return InstagramApp
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Returns the InstagramClient service.
     *
     * @return InstagramClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the OAuth 2.0 client service.
     *
     * @return OAuth2Client
     */
    public function getOAuth2Client()
    {
        if (!$this->oAuth2Client instanceof OAuth2Client) {
            $app = $this->getApp();
            $client = $this->getClient();
            $this->oAuth2Client = new OAuth2Client($app, $client, $this->defaultGraphVersion);
        }

        return $this->oAuth2Client;
    }

    /**
     * Returns the last response returned from Graph.
     *
     * @return InstagramResponse|InstagramBatchResponse|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Returns the URL detection handler.
     *
     * @return UrlDetectionInterface
     */
    public function getUrlDetectionHandler()
    {
        return $this->urlDetectionHandler;
    }

    /**
     * Changes the URL detection handler.
     *
     * @param UrlDetectionInterface $urlDetectionHandler
     */
    private function setUrlDetectionHandler(UrlDetectionInterface $urlDetectionHandler)
    {
        $this->urlDetectionHandler = $urlDetectionHandler;
    }

    /**
     * Returns the default AccessToken entity.
     *
     * @return AccessToken|null
     */
    public function getDefaultAccessToken()
    {
        return $this->defaultAccessToken;
    }

    /**
     * Sets the default access token to use with requests.
     *
     * @param AccessToken|string $accessToken The access token to save.
     *
     * @throws \InvalidArgumentException
     */
    public function setDefaultAccessToken($accessToken)
    {
        if (is_string($accessToken)) {
            $this->defaultAccessToken = new AccessToken($accessToken);

            return;
        }

        if ($accessToken instanceof AccessToken) {
            $this->defaultAccessToken = $accessToken;

            return;
        }

        throw new \InvalidArgumentException('The default access token must be of type "string" or Instagram\AccessToken');
    }

    /**
     * Returns the default Graph version.
     *
     * @return string
     */
    public function getDefaultGraphVersion()
    {
        return $this->defaultGraphVersion;
    }

    /**
     * Returns the redirect login helper.
     *
     * @return InstagramRedirectLoginHelper
     */
    public function getRedirectLoginHelper()
    {
        return new InstagramRedirectLoginHelper(
            $this->getOAuth2Client(),
            $this->persistentDataHandler,
            $this->urlDetectionHandler,
            $this->pseudoRandomStringGenerator
        );
    }

    /**
     * Sends a GET request to Graph and returns the result.
     *
     * @param string                  $endpoint
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     *
     * @return InstagramResponse
     *
     * @throws InstagramSDKException
     */
    public function get($endpoint, $accessToken = null, $eTag = null)
    {
        return $this->sendRequest(
            'GET',
            $endpoint,
            $params = [],
            $accessToken,
            $eTag
        );
    }

    /**
     * Sends a POST request to Graph and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     *
     * @return InstagramResponse
     *
     * @throws InstagramSDKException
     */
    public function post($endpoint, array $params = [], $accessToken = null, $eTag = null)
    {
        return $this->sendRequest(
            'POST',
            $endpoint,
            $params,
            $accessToken,
            $eTag
        );
    }

    /**
     * Sends a DELETE request to Graph and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     *
     * @return InstagramResponse
     *
     * @throws InstagramSDKException
     */
    public function delete($endpoint, array $params = [], $accessToken = null, $eTag = null)
    {
        return $this->sendRequest(
            'DELETE',
            $endpoint,
            $params,
            $accessToken,
            $eTag
        );
    }

    /**
     * Sends a request to Graph for the next page of results.
     *
     * @param GraphEdge $graphEdge The GraphEdge to paginate over.
     *
     * @return GraphEdge|null
     *
     * @throws InstagramSDKException
     */
    public function next(GraphEdge $graphEdge)
    {
        return $this->getPaginationResults($graphEdge, 'next');
    }

    /**
     * Sends a request to Graph for the previous page of results.
     *
     * @param GraphEdge $graphEdge The GraphEdge to paginate over.
     *
     * @return GraphEdge|null
     *
     * @throws InstagramSDKException
     */
    public function previous(GraphEdge $graphEdge)
    {
        return $this->getPaginationResults($graphEdge, 'previous');
    }

    /**
     * Sends a request to Graph for the next page of results.
     *
     * @param GraphEdge $graphEdge The GraphEdge to paginate over.
     * @param string    $direction The direction of the pagination: next|previous.
     *
     * @return GraphEdge|null
     *
     * @throws InstagramSDKException
     */
    public function getPaginationResults(GraphEdge $graphEdge, $direction)
    {
        $paginationRequest = $graphEdge->getPaginationRequest($direction);
        if (!$paginationRequest) {
            return null;
        }

        $this->lastResponse = $this->client->sendRequest($paginationRequest);

        // Keep the same GraphNode subclass
        $subClassName = $graphEdge->getSubClassName();
        $graphEdge = $this->lastResponse->getGraphEdge($subClassName, false);

        return count($graphEdge) > 0 ? $graphEdge : null;
    }

    /**
     * Sends a request to Graph and returns the result.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     * @param string|null             $graphVersion
     *
     * @return InstagramResponse
     *
     * @throws InstagramSDKException
     */
    public function sendRequest($method, $endpoint, array $params = [], $accessToken = null, $eTag = null, $graphVersion = null)
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;
        $graphVersion = $graphVersion ?: $this->defaultGraphVersion;
        $request = $this->request($method, $endpoint, $params, $accessToken, $eTag, $graphVersion);

        return $this->lastResponse = $this->client->sendRequest($request);
    }

    /**
     * Sends a batched request to Graph and returns the result.
     *
     * @param array                   $requests
     * @param AccessToken|string|null $accessToken
     * @param string|null             $graphVersion
     *
     * @return InstagramBatchResponse
     *
     * @throws InstagramSDKException
     */
    public function sendBatchRequest(array $requests, $accessToken = null, $graphVersion = null)
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;
        $graphVersion = $graphVersion ?: $this->defaultGraphVersion;
        $batchRequest = new InstagramBatchRequest(
            $this->app,
            $requests,
            $accessToken,
            $graphVersion
        );

        return $this->lastResponse = $this->client->sendBatchRequest($batchRequest);
    }

    /**
     * Instantiates an empty InstagramBatchRequest entity.
     *
     * @param  AccessToken|string|null $accessToken  The top-level access token. Requests with no access token
     *                                               will fallback to this.
     * @return InstagramBatchRequest
     */
    public function newBatchRequest($accessToken = null)
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;

        return new InstagramBatchRequest(
            $this->app,
            [],
            $accessToken
        );
    }

    /**
     * Instantiates a new InstagramRequest entity.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $eTag
     * @param string|null             $graphVersion
     *
     * @return InstagramRequest
     *
     * @throws InstagramSDKException
     */
    public function request($method, $endpoint, array $params = [], $accessToken = null, $eTag = null)
    {
        $accessToken = $accessToken ?: $this->defaultAccessToken;

        return new InstagramRequest(
            $this->app,
            $accessToken,
            $method,
            $endpoint,
            $params,
            $eTag
        );
    }
}
