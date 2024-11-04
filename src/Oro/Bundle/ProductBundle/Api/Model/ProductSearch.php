<?php

namespace Oro\Bundle\ProductBundle\Api\Model;

/**
 * Represents the product search result.
 */
class ProductSearch implements \ArrayAccess
{
    private array $properties = [];

    public function __construct(int $id)
    {
        $this->properties['id'] = $id;
    }

    #[\Override]
    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->properties);
    }

    #[\Override]
    public function offsetGet($offset): mixed
    {
        if (!\array_key_exists($offset, $this->properties)) {
            throw new \InvalidArgumentException(sprintf('The "%s" property does not exist.', $offset));
        }

        return $this->properties[$offset];
    }

    #[\Override]
    public function offsetSet($offset, $value): void
    {
        $this->properties[$offset] = $value;
    }

    #[\Override]
    public function offsetUnset($offset): void
    {
        unset($this->properties[$offset]);
    }
}
