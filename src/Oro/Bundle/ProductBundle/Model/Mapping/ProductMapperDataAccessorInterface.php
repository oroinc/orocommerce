<?php

namespace Oro\Bundle\ProductBundle\Model\Mapping;

/**
 * Represents a service to access data required to map a product for each item in an item collection.
 */
interface ProductMapperDataAccessorInterface
{
    public function getItem(object $collection, int $itemIndex): object;

    public function getItemSku(object $item): ?string;

    public function getItemOrganizationName(object $item): ?string;

    public function getProductSku(mixed $product): ?string;

    public function getProductOrganizationId(mixed $product): ?int;

    public function updateItem(object $item, mixed $product): void;
}
