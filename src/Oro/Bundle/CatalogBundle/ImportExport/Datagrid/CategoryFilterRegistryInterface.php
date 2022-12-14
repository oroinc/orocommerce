<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Datagrid;

/**
 * Simple category filters storage
 */
interface CategoryFilterRegistryInterface
{
    public const DEFAULT_NAME = 'default';

    public function add(CategoryFilterInterface $filter): void;
    public function get(string $name): CategoryFilterInterface;
}
