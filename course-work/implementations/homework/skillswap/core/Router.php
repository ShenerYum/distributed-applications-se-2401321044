<?php

namespace App\Core;

use App\Core\Container;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use RuntimeException;

class Router
{
	protected array $routes = [];

	protected string $method;
	protected string $uri;

	protected array $params = [];

	public function __construct(protected Container $container)
	{
		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
	}


	public function get(string $uri, array $action, array $middleware = []): void
	{
		$this->addRoute('GET', $uri, $action, $middleware);
	}

	public function post(string $uri, array $action, array $middleware = []): void
	{
		$this->addRoute('POST', $uri, $action, $middleware);
	}

	public function put(string $uri, array $action, array $middleware = []): void
	{
		$this->addRoute('PUT', $uri, $action, $middleware);
	}

	public function delete(string $uri, array $action, array $middleware = []): void
	{
		$this->addRoute('DELETE', $uri, $action, $middleware);
	}

	protected function addRoute(string $method, string $uri, array $action, array $middleware = []): void
	{
		$this->routes[$method][$uri] = [
			'action' => $action,
			'middleware' => $middleware
		];
	}


	public function dispatch(): void
	{

		$route = $this->resolveRoute();

		$controllerClass = $route['action'][0];
		$method = $route['action'][1];
		$middlewareStack = $route['middleware'] ?? [];

		$controller = $this->container->make($controllerClass);


		if (!method_exists($controller, $method)) {
			throw new NotFoundException(
				"Method {$method} not found in {$controllerClass}"
			);
		}

		$core = function () use ($controller, $method) {
			$response = $controller->$method(...$this->params);

			if (!$response instanceof Response) {
				throw new RuntimeException(
					"Controller {" . $controller::class . "} must return instance of Response",
					400
				);
			}

			return $response;
		};

		$response = $this->runMiddleware($middlewareStack, $core);

		if (!$response instanceof Response) {
			throw new RuntimeException("Invalid response from middleware pipeline", 400);
		}

		$response->send();
	}

	protected function runMiddleware(array $stack, callable $core): Response
	{
		$pipeline = array_reduce(
			array_reverse($stack),
			function ($next, $middlewareClass) {
				return function () use ($next, $middlewareClass) {

					$middleware = $this->container->make($middlewareClass);

					if (!method_exists($middleware, 'handle')) {
						throw new \BadFunctionCallException(
							"Middleware {$middlewareClass} must have handle() method",
							400
						);
					}

					return $middleware->handle($next);
				};
			},
			$core
		);

		return $pipeline();
	}


	protected function resolveRoute(): array
	{
		$routes = $this->routes[$this->method] ?? [];

		foreach ($routes as $route => $data) {

			$pattern = preg_replace(
				'#\{([a-zA-Z_]+)\}#',
				'([^/]+)',
				$route
			);

			if (preg_match("#^{$pattern}$#", $this->uri, $matches)) {
				array_shift($matches);
				$this->params = $matches;

				return $data;
			}
		}

		throw new NotFoundException(
			"Route not found: {$this->method} {$this->uri}"
		);
	}


	public function params(): array
	{
		return $this->params;
	}
}
