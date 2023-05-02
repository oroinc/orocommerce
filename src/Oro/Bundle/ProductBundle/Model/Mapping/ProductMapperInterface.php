<?php

namespace Oro\Bundle\ProductBundle\Model\Mapping;

/**
 * Represents a service to map a product for each item in an item collection.
 */
interface ProductMapperInterface
{
    public function mapProducts(object $collection): void;
}
