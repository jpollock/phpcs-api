<?php

namespace PhpcsApi;

/**
 * Response class to handle HTTP responses.
 */
class Response
{
    /**
     * HTTP status code.
     *
     * @var int
     */
    public $statusCode = 200;

    /**
     * Response headers.
     *
     * @var array
     */
    public $headers = [];

    /**
     * Response body.
     *
     * @var string|null
     */
    public $body;

    /**
     * Create a new Response instance.
     *
     * @param int         $statusCode HTTP status code.
     * @param string|null $body       Response body.
     * @param array       $headers    Response headers.
     */
    public function __construct(int $statusCode = 200, ?string $body = null, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data       Data to encode as JSON.
     * @param int   $statusCode HTTP status code.
     *
     * @return self
     */
    public static function json($data, int $statusCode = 200): self
    {
        $response = new self($statusCode);
        return $response->withJson($data);
    }

    /**
     * Set the response body as JSON.
     *
     * @param mixed $data Data to encode as JSON.
     *
     * @return self
     */
    public function withJson($data): self
    {
        $this->headers['Content-Type'] = 'application/json';
        $this->body = json_encode($data);
        return $this;
    }

    /**
     * Set a response header.
     *
     * @param string $name  Header name.
     * @param string $value Header value.
     *
     * @return self
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send the response.
     *
     * @return void
     */
    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Add API version header
        $apiVersion = Config::get('api_version', 'v1');
        $this->headers['X-API-Version'] = $apiVersion;

        // Set headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send body
        echo $this->body;
    }

    /**
     * Create a 404 Not Found response.
     *
     * @param string $message Error message.
     *
     * @return self
     */
    public static function notFound(string $message = 'Not Found'): self
    {
        return self::json(['error' => $message], 404);
    }

    /**
     * Create a 400 Bad Request response.
     *
     * @param string $message Error message.
     *
     * @return self
     */
    public static function badRequest(string $message = 'Bad Request'): self
    {
        return self::json(['error' => $message], 400);
    }

    /**
     * Create a 500 Internal Server Error response.
     *
     * @param string $message Error message.
     *
     * @return self
     */
    public static function serverError(string $message = 'Internal Server Error'): self
    {
        return self::json(['error' => $message], 500);
    }
}
