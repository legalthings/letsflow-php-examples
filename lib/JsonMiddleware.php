<?php

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Psr7\Response;

class JsonMiddleware
{
    /**
     * Invoke the middleware.
     *
     * @param callable $handler
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        return function(RequestInterface $request, $options) use ($handler) {
            if (!$request->hasHeader('Accept') || !$request->getHeader('Accept') === 'application/json') {
                return $handler($request, $options);
            }
            return $handler($request, $options)->then(static function(Response $response) use ($handler) {
                if ($response->getStatusCode() != 200) {
                    return $response;
                }
                list($contentType) = explode(';', $response->getHeaderLine('Content-Type'));
                if ($contentType !== 'application/json') {
                    echo $response->getBody();
                    throw new \UnexpectedValueException("Expected application/json, got $contentType");
                }
                json_decode((string)$response->getBody());
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \UnexpectedValueException("Response is not not valid JSON: ". json_last_error_msg());
                }
                return $response;
            });
        };
    }
}