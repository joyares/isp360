<?php

declare(strict_types=1);

namespace Psr\Container;

interface ContainerInterface
{
    /**
     * @return mixed
     */
    public function get(string $id);

    public function has(string $id): bool;
}

interface ContainerExceptionInterface extends \Throwable
{
}

interface NotFoundExceptionInterface extends ContainerExceptionInterface
{
}
