<?php

namespace Maztech\HttpClients;

use Maztech\Http\GraphRawResponse;
use Maztech\Exceptions\InstagramSDKException;

class InstagramStreamHttpClient implements InstagramHttpClientInterface
{
    /**
     * @var InstagramStream Procedural stream wrapper as object.
     */
    protected $InstagramStream;

    /**
     * @param InstagramStream|null Procedural stream wrapper as object.
     */
    public function __construct(InstagramStream $InstagramStream = null)
    {
        $this->InstagramStream = $InstagramStream ?: new InstagramStream();
    }

    /**
     * @inheritdoc
     */
    public function send($url, $method, $body, array $headers, $timeOut)
    {
        $options = [
            'http' => [
                'method' => $method,
                'header' => $this->compileHeader($headers),
                'content' => $body,
                'timeout' => $timeOut,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => true, // All root certificates are self-signed
                'cafile' => __DIR__ . '/certs/DigiCertHighAssuranceEVRootCA.pem',
            ],
        ];

        $this->InstagramStream->streamContextCreate($options);
        $rawBody = $this->InstagramStream->fileGetContents($url);
        $rawHeaders = $this->InstagramStream->getResponseHeaders();

        if ($rawBody === false || empty($rawHeaders)) {
            throw new InstagramSDKException('Stream returned an empty response', 660);
        }

        $rawHeaders = implode("\r\n", $rawHeaders);

        return new GraphRawResponse($rawHeaders, $rawBody);
    }

    /**
     * Formats the headers for use in the stream wrapper.
     *
     * @param array $headers The request headers.
     *
     * @return string
     */
    public function compileHeader(array $headers)
    {
        $header = [];
        foreach ($headers as $k => $v) {
            $header[] = $k . ': ' . $v;
        }

        return implode("\r\n", $header);
    }
}
