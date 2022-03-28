<?php

namespace Maztech;

use ArrayIterator;
use IteratorAggregate;
use ArrayAccess;

/**
 * Class InstagramBatchResponse
 *
 * @package Instagram
 */
class InstagramBatchResponse extends InstagramResponse implements IteratorAggregate, ArrayAccess
{
    /**
     * @var InstagramBatchRequest The original entity that made the batch request.
     */
    protected $batchRequest;

    /**
     * @var array An array of InstagramResponse entities.
     */
    protected $responses = [];

    /**
     * Creates a new Response entity.
     *
     * @param InstagramBatchRequest $batchRequest
     * @param InstagramResponse     $response
     */
    public function __construct(InstagramBatchRequest $batchRequest, InstagramResponse $response)
    {
        $this->batchRequest = $batchRequest;

        $request = $response->getRequest();
        $body = $response->getBody();
        $httpStatusCode = $response->getHttpStatusCode();
        $headers = $response->getHeaders();
        parent::__construct($request, $body, $httpStatusCode, $headers);

        $responses = $response->getDecodedBody();
        $this->setResponses($responses);
    }

    /**
     * Returns an array of InstagramResponse entities.
     *
     * @return array
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * The main batch response will be an array of requests so
     * we need to iterate over all the responses.
     *
     * @param array $responses
     */
    public function setResponses(array $responses)
    {
        $this->responses = [];

        foreach ($responses as $key => $graphResponse) {
            $this->addResponse($key, $graphResponse);
        }
    }

    /**
     * Add a response to the list.
     *
     * @param int        $key
     * @param array|null $response
     */
    public function addResponse($key, $response)
    {
        $originalRequestName = isset($this->batchRequest[$key]['name']) ? $this->batchRequest[$key]['name'] : $key;
        $originalRequest = isset($this->batchRequest[$key]['request']) ? $this->batchRequest[$key]['request'] : null;

        $httpResponseBody = isset($response['body']) ? $response['body'] : null;
        $httpResponseCode = isset($response['code']) ? $response['code'] : null;
        // @TODO With PHP 5.5 support, this becomes array_column($response['headers'], 'value', 'name')
        $httpResponseHeaders = isset($response['headers']) ? $this->normalizeBatchHeaders($response['headers']) : [];

        $this->responses[$originalRequestName] = new InstagramResponse(
            $originalRequest,
            $httpResponseBody,
            $httpResponseCode,
            $httpResponseHeaders
        );
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new ArrayIterator($this->responses);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->addResponse($offset, $value);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->responses[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->responses[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return isset($this->responses[$offset]) ? $this->responses[$offset] : null;
    }

    /**
     * Converts the batch header array into a standard format.
     * @TODO replace with array_column() when PHP 5.5 is supported.
     *
     * @param array $batchHeaders
     *
     * @return array
     */
    private function normalizeBatchHeaders(array $batchHeaders)
    {
        $headers = [];

        foreach ($batchHeaders as $header) {
            $headers[$header['name']] = $header['value'];
        }

        return $headers;
    }
}
