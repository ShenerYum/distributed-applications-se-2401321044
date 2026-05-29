<?php

namespace App\Core;

use ReflectionClass;

class Container
{
	protected array $bindings = [];

	/**
	 * Bind an abstract type to a concrete implementation. The concrete can be a class name or a factory function (closure).
	 */
	public function bind(string $abstract, callable|string $concrete): void
	{
		$this->bindings[$abstract] = $concrete;
	}

	/**
	 * Bind a singleton. The same instance will be returned every time the abstract type is resolved.
	 */
	public function singleton(string $abstract, callable|string $concrete): void
	{
		$this->bindings[$abstract] = function () use ($concrete) {
			static $instance;

			if (!$instance) {
				$instance = is_callable($concrete)
					? $concrete($this)
					: $this->build($concrete);
			}

			return $instance;
		};
	}

	/**
	 * Resolve an instance of the given abstract type.
	 * If a binding exists, it will be used; otherwise,
	 * the container will attempt to auto-resolve the class.
	 */
	public function make(string $class)
	{
		// 1. If explicitly bound
		if (isset($this->bindings[$class])) {
			$binding = $this->bindings[$class];

			return is_callable($binding)
				? $binding($this)
				: $this->build($binding);
		}

		// 2. Auto-resolve
		return $this->build($class);
	}

	/**
	 * Build an instance of the given class, resolving its dependencies recursively.
	 */
	protected function build(string $class)
	{
		$reflector = new ReflectionClass($class);

		if (!$reflector->isInstantiable()) {
			throw new \Exception("Class {$class} not instantiable");
		}

		$constructor = $reflector->getConstructor();

		if (!$constructor) {
			return new $class;
		}

		$dependencies = [];

		foreach ($constructor->getParameters() as $param) {

			$type = $param->getType();

			if (!$type) {
				throw new \Exception(
					"Cannot resolve parameter {$param->getName()} in {$class}"
				);
			}

			$dependencies[] = $this->make($type->getName());
		}

		return $reflector->newInstanceArgs($dependencies);
	}
}
