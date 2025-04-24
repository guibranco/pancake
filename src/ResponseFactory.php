<?php

namespace GuiBranco\Pancake;

use GuiBranco\Pancake\Response;

class ResponseFactory
{
    /**
     * Creates a successful response.
     *
     * @param string $body The body content of the response.
     * @param string $url The URL associated with the response.
     * @param array $headers An array of headers to include in the response.
     * @param int $statusCode The HTTP status code for the response (default is 200).
     * @return Response Returns an instance of the response.
     */
    public function success(string $body, string $url, array $headers, int $statusCode = 200): Response
    {
        return new Response(true, $body, '', $statusCode, $url, $headers);
    }

    /**
     * Generates an error response.
     *
     * @param string $message The error message to be displayed.
     * @param string $url The URL to redirect to.
     * @param int $statusCode The HTTP status code for the error response. Default is 400.
     * @return Response Returns an instance of the response with the error details.
     */
    public function error(string $message, string $url, int $statusCode = 400): Response
    {
        return new Response(false, null, $message, $statusCode, $url, null);
    }
}