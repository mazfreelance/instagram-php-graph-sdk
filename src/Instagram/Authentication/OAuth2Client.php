<?php

namespace Instagram\Authentication;

use Instagram\InstagramApp;
use Instagram\InstagramRequest;
use Instagram\InstagramResponse;
use Instagram\InstagramClient;
use Instagram\Exceptions\InstagramResponseException;
use Instagram\Exceptions\InstagramSDKException;

/**
 * Class OAuth2Client
 *
 * @package Instagram
 */
class OAuth2Client
{
    /**
     * @const string The base authorization URL.
     */
    const BASE_AUTHORIZATION_URL = 'https://api.instagram.com';

    /**
     * The InstagramApp entity.
     *
     * @var InstagramApp
     */
    protected $app;

    /**
     * The Instagram client.
     *
     * @var InstagramClient
     */
    protected $client;

    /**
     * The version of the Graph API to use.
     *
     * @var string
     */
    protected $graphVersion;

    /**
     * The last request sent to Graph.
     *
     * @var InstagramRequest|null
     */
    protected $lastRequest;

    /**
     * @param InstagramApp    $app
     * @param InstagramClient $client
     * @param string|null    $graphVersion The version of the Graph API to use.
     */
    public function __construct(InstagramApp $app, InstagramClient $client)
    {
        $this->app = $app;
        $this->client = $client;
    }

    /**
     * Returns the last InstagramRequest that was sent.
     * Useful for debugging and testing.
     *
     * @return InstagramRequest|null
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Generates an authorization URL to begin the process of authenticating a user.
     *
     * @param string $redirectUrl The callback URL to redirect to.
     * @param string $state       The CSPRNG-generated CSRF value.
     * @param array  $scope       An array of permissions to request.
     * @param array  $params      An array of parameters to generate URL.
     * @param string $separator   The separator to use in http_build_query().
     *
     * @return string
     */
    public function getAuthorizationUrl($redirectUrl, $state, array $scope = [], array $params = [], $separator = '&')
    {
        $params += [
            'client_id' => $this->app->getId(),
            'state' => $state,
            'response_type' => 'code',
            'redirect_uri' => $redirectUrl,
            'scope' => implode(',', $scope)
        ];
        
        return static::BASE_AUTHORIZATION_URL . '/oauth/authorize?' . http_build_query($params, '', $separator);
    }

    /**
     * Get a valid access token from a code.
     *
     * @param string $code
     * @param string $redirectUri
     *
     * @return AccessToken
     *
     * @throws InstagramSDKException
     */
    public function getAccessTokenFromCode($code, $redirectUri = '')
    {
        $params = [
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];

        return $this->requestAnAccessToken($params);
    }

    /**
     * Exchanges a short-lived access token with a long-lived access token.
     *
     * @param AccessToken|string $accessToken
     *
     * @return AccessToken
     *
     * @throws InstagramSDKException
     */
    public function getLongLivedAccessToken($accessToken)
    {
        $accessToken = $accessToken instanceof AccessToken ? $accessToken->getValue() : $accessToken;
        $params = [
            'grant_type' => 'fb_exchange_token',
            'fb_exchange_token' => $accessToken,
        ];

        return $this->requestAnAccessToken($params);
    }

    /**
     * Get a valid code from an access token.
     *
     * @param AccessToken|string $accessToken
     * @param string             $redirectUri
     *
     * @return AccessToken
     *
     * @throws InstagramSDKException
     */
    public function getCodeFromLongLivedAccessToken($accessToken, $redirectUri = '')
    {
        $params = [
            'redirect_uri' => $redirectUri,
        ];

        $response = $this->sendRequestWithClientParams('/oauth/client_code', $params, $accessToken);
        $data = $response->getDecodedBody();

        if (!isset($data['code'])) {
            throw new InstagramSDKException('Code was not returned from Graph.', 401);
        }

        return $data['code'];
    }

    /**
     * Send a request to the OAuth endpoint.
     *
     * @param array $params
     *
     * @return AccessToken
     *
     * @throws InstagramSDKException
     */
    protected function requestAnAccessToken(array $params)
    {
        $response = $this->sendRequestWithClientParams('/oauth/access_token', $params);
        $data = $response->getDecodedBody();

        if (!isset($data['access_token'])) {
            throw new InstagramSDKException('Access token was not returned from Graph.', 401);
        }

        // Graph returns two different key names for expiration time
        // on the same endpoint. Doh! :/
        $expiresAt = 0;
        if (isset($data['expires'])) {
            // For exchanging a short lived token with a long lived token.
            // The expiration time in seconds will be returned as "expires".
            $expiresAt = time() + $data['expires'];
        } elseif (isset($data['expires_in'])) {
            // For exchanging a code for a short lived access token.
            // The expiration time in seconds will be returned as "expires_in".
            // See: https://developers.facebook.com/docs/facebook-login/access-tokens#long-via-code
            $expiresAt = time() + $data['expires_in'];
        }

        return new AccessToken($data['access_token'], $expiresAt, $data['user_id']);
    }

    /**
     * Send a request to Graph with an app access token.
     *
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     *
     * @return InstagramResponse
     *
     * @throws InstagramResponseException
     */
    protected function sendRequestWithClientParams($endpoint, array $params, $accessToken = null)
    {
        $params += $this->getClientParams();

        $accessToken = $accessToken ?: $this->app->getAccessToken();

        $this->lastRequest = new InstagramRequest(
            $this->app,
            $accessToken,
            'POST',
            $endpoint,
            $params,
            null
        );

        // dd($this->client, $params, $this->lastRequest );
        return $this->client->sendRequest($this->lastRequest, 'authorization');
    }

    /**
     * Returns the client_* params for OAuth requests.
     *
     * @return array
     */
    protected function getClientParams()
    {
        return [
            'client_id' => $this->app->getId(),
            'client_secret' => $this->app->getSecret(),
        ];
    }
}
