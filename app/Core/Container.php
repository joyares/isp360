<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use Throwable;

class Container implements ContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $definitions = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * @param mixed $concrete
     */
    public function bind(string $id, $concrete): void
    {
        $this->definitions[$id] = $concrete;
    }

    /**
     * @param mixed $concrete
     */
    public function singleton(string $id, $concrete): void
    {
        $this->definitions[$id] = $concrete;
        $this->instances[$id] = $this->resolveDefinition($id, $concrete);
    }

    /**
     * @param mixed $value
     */
    public function instance(string $id, $value): void
    {
        $this->instances[$id] = $value;
    }

    /**
     * @return mixed
     */
    public function get(string $id)
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (array_key_exists($id, $this->definitions)) {
            return $this->resolveDefinition($id, $this->definitions[$id]);
        }

        if (class_exists($id)) {
            return $this->resolve($id);
        }

        throw new NotFoundException(sprintf('No entry was found for "%s".', $id));
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->definitions)
            || array_key_exists($id, $this->instances)
            || class_exists($id);
    }

    /**
     * @param mixed $definition
     * @return mixed
     */
    private function resolveDefinition(string $id, $definition)
    {
        if ($definition instanceof Closure) {
            try {
                return $definition($this);
            } catch (Throwable $exception) {
                throw new ContainerException(
                    sprintf('Failed to resolve closure for "%s": %s', $id, $exception->getMessage()),
                    0,
                    $exception
                );
            }
        }

        if (is_string($definition)) {
            return $this->resolve($definition);
        }

        return $definition;
    }

    /**
     * @return mixed
     */
    private function resolve(string $className)
    {
        try {
            $reflectionClass = new ReflectionClass($className);
        } catch (ReflectionException $exception) {
            throw new ContainerException(
                sprintf('Class "%s" could not be reflected.', $className),
                0,
                $exception
            );
        }

        if (!$reflectionClass->isInstantiable()) {
            throw new ContainerException(sprintf('Class "%s" is not instantiable.', $className));
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return new $className();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new ContainerException(
                sprintf(
                    'Unable to resolve parameter "$%s" while constructing "%s".',
                    $parameter->getName(),
                    $className
                )
            );
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}

class ContainerException extends Exception implements ContainerExceptionInterface
{
}

class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
