<?php

namespace PhpcsApi;

/**
 * Request class to handle HTTP requests.
 */
class Request
{
    /**
     * HTTP method.
     *
     * @var string
     */
    public $method;

    /**
     * Request path.
     *
     * @var string
     */
    public $path;

    /**
     * Request body.
     *
     * @var array|null
     */
    public $body;

    /**
     * Request headers.
     *
     * @var array
     */
    public $headers;

    /**
     * Create a new Request instance.
     *
     * @param string $method  HTTP method.
     * @param string $path    Request path.
     * @param array  $body    Request body.
     * @param array  $headers Request headers.
     */
    public function __construct(string $method, string $path, ?array $body = null, array $headers = [])
    {
        $this->method = $method;
        $this->path = $path;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * Create a Request instance from PHP globals.
     *
     * @return self
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Handle malformed URLs (like double slashes) that cause parse_url to return null
        if ($path === null) {
            // Clean up the URI by replacing multiple consecutive slashes with a single slash
            $uri = preg_replace('|/{2,}|', '/', $uri);
            $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        }
        
        // Get headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }

        // Get request body
        $body = null;
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $input = file_get_contents('php://input');
                $body = json_decode($input, true);
            } else {
                $body = $_POST;
            }
        }

        return new self($method, $path, $body, $headers);
    }

    /**
     * Get a query parameter.
     *
     * @param string $name    Parameter name.
     * @param mixed  $default Default value.
     *
     * @return mixed
     */
    public function getQueryParam(string $name, $default = null)
    {
        return $_GET[$name] ?? $default;
    }
}
