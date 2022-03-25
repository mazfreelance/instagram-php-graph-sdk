<?php

namespace Instagram\Exceptions;

use Instagram\InstagramResponse;

/**
 * Class InstagramResponseException
 *
 * @package Instagram
 */
class InstagramResponseException extends InstagramSDKException
{
    /**
     * @var InstagramResponse The response that threw the exception.
     */
    protected $response;

    /**
     * @var array Decoded response.
     */
    protected $responseData;

    /**
     * Creates a InstagramResponseException.
     *
     * @param InstagramResponse     $response          The response that threw the exception.
     * @param InstagramSDKException $previousException The more detailed exception.
     */
    public function __construct(InstagramResponse $response, InstagramSDKException $previousException = null)
    {
        $this->response = $response;
        $this->responseData = $response->getDecodedBody();

        $errorMessage = $this->get('message', 'Unknown error from Graph.');
        $errorCode = $this->get('code', -1);

        parent::__construct($errorMessage, $errorCode, $previousException);
    }

    /**
     * A factory for creating the appropriate exception based on the response from Graph.
     *
     * @param InstagramResponse $response The response that threw the exception.
     *
     * @return InstagramResponseException
     */
    public static function create(InstagramResponse $response)
    {
        $data = $response->getDecodedBody();

        if (!isset($data['error']['code']) && isset($data['code'])) {
            $data = ['error' => $data];
        }

        $code = isset($data['error']['code']) ? $data['error']['code'] : null;
        $message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error from Graph.';

        if (isset($data['error']['error_subcode'])) {
            switch ($data['error']['error_subcode']) {
                // Other authentication issues
                case 458:
                case 459:
                case 460:
                case 463:
                case 464:
                case 467:
                    return new static($response, new InstagramAuthenticationException($message, $code));
                // Video upload resumable error
                case 1363030:
                case 1363019:
                case 1363033:
                case 1363021:
                case 1363041:
                    return new static($response, new InstagramResumableUploadException($message, $code));
                case 1363037:
                    $previousException = new InstagramResumableUploadException($message, $code);

                    $startOffset = isset($data['error']['error_data']['start_offset']) ? (int) $data['error']['error_data']['start_offset'] : null;
                    $previousException->setStartOffset($startOffset);

                    $endOffset = isset($data['error']['error_data']['end_offset']) ? (int) $data['error']['error_data']['end_offset'] : null;
                    $previousException->setEndOffset($endOffset);

                    return new static($response, $previousException);
            }
        }

        switch ($code) {
            // Login status or token expired, revoked, or invalid
            case 100:
            case 102:
            case 190:
                return new static($response, new InstagramAuthenticationException($message, $code));

            // Server issue, possible downtime
            case 1:
            case 2:
                return new static($response, new InstagramServerException($message, $code));

            // API Throttling
            case 4:
            case 17:
            case 32:
            case 341:
            case 613:
                return new static($response, new InstagramThrottleException($message, $code));

            // Duplicate Post
            case 506:
                return new static($response, new InstagramClientException($message, $code));
        }

        // Missing Permissions
        if ($code == 10 || ($code >= 200 && $code <= 299)) {
            return new static($response, new InstagramAuthorizationException($message, $code));
        }

        // OAuth authentication error
        if (isset($data['error']['type']) && $data['error']['type'] === 'OAuthException') {
            return new static($response, new InstagramAuthenticationException($message, $code));
        }

        // All others
        return new static($response, new InstagramOtherException($message, $code));
    }

    /**
     * Checks isset and returns that or a default value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function get($key, $default = null)
    {
        if (isset($this->responseData['error'][$key])) {
            return $this->responseData['error'][$key];
        }

        return $default;
    }

    /**
     * Returns the HTTP status code
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->response->getHttpStatusCode();
    }

    /**
     * Returns the sub-error code
     *
     * @return int
     */
    public function getSubErrorCode()
    {
        return $this->get('error_subcode', -1);
    }

    /**
     * Returns the error type
     *
     * @return string
     */
    public function getErrorType()
    {
        return $this->get('type', '');
    }

    /**
     * Returns the raw response used to create the exception.
     *
     * @return string
     */
    public function getRawResponse()
    {
        return $this->response->getBody();
    }

    /**
     * Returns the decoded response used to create the exception.
     *
     * @return array
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * Returns the response entity used to create the exception.
     *
     * @return InstagramResponse
     */
    public function getResponse()
    {
        return $this->response;
    }
}
