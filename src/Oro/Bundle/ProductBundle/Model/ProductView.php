<?php

namespace Oro\Bundle\ProductBundle\Model;

/**
 * A model that represents a product entity to be rendered on the storefront.
 */
class ProductView implements \IteratorAggregate
{
    /** @var array [name => value, ...] */
    private array $attributes = [];

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    public function __unset(string $name): void
    {
        $this->remove($name);
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    public function get(string $name): mixed
    {
        if (!\array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf('The "%s" attribute does not exist.', $name));
        }

        return $this->attributes[$name];
    }

    public function set(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function remove(string $name): void
    {
        unset($this->attributes[$name]);
    }

    /**
     * Returns an ID of a product entity this view represents.
     */
    public function getId(): int
    {
        return $this->get('id');
    }

    public function __toString(): string
    {
        $name = $this->attributes['name'] ?? null;
        if ($name) {
            return (string)$name;
        }

        return (string)($this->attributes['sku'] ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->attributes);
    }
}
