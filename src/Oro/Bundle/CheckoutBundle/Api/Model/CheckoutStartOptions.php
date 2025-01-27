<?php

namespace Oro\Bundle\CheckoutBundle\Api\Model;

/**
 * Represents options that can be provided during a checkout creation based on other entities.
 */
final class CheckoutStartOptions
{
    private array $properties = [];

    public function __isset(string $name): bool
    {
        return true;
    }

    public function __get(string $name): mixed
    {
        return $this->properties[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->properties[$name] = $value;
    }

    public function __unset(string $name): void
    {
        unset($this->properties[$name]);
    }
}
