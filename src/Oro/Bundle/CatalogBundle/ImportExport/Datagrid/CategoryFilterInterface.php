<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Datagrid;

use Doctrine\ORM\QueryBuilder;

/**
 * Filtering util for different category query paths
 */
interface CategoryFilterInterface
{
    public function getName(): string;
    public function getFieldName(QueryBuilder $qb): string;
    public function prepareQueryBuilder(QueryBuilder $qb): void;
}
