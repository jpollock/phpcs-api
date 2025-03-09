<?php

namespace PhpcsApi;

/**
 * Router class to handle API routing.
 */
class Router
{
    /**
     * Routes.
     *
     * @var array
     */
    private $routes = [];

    /**
     * Add a route.
     *
     * @param string   $method  HTTP method.
     * @param string   $path    Route path.
     * @param callable $handler Route handler.
     *
     * @return self
     */
    public function addRoute(string $method, string $path, callable $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];

        return $this;
    }

    /**
     * Dispatch a request.
     *
     * @param Request $request Request instance.
     *
     * @return Response
     */
    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($this->matchRoute($request, $route)) {
                try {
                    return call_user_func($route['handler'], $request);
                } catch (\Exception $e) {
                    return Response::serverError($e->getMessage());
                }
            }
        }

        return Response::notFound('Route not found');
    }

    /**
     * Check if a route matches a request.
     *
     * @param Request $request Request instance.
     * @param array   $route   Route definition.
     *
     * @return bool
     */
    private function matchRoute(Request $request, array $route): bool
    {
        // Check method
        if ($request->method !== $route['method']) {
            return false;
        }

        // Simple path matching for now
        return $request->path === $route['path'];
    }
}
