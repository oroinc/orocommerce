<?php

namespace Oro\Bundle\ProductBundle\Expression;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

/**
 * Defines the contract for converting query designer definitions to query builders.
 *
 * Implementations of this interface extend the query conversion process by transforming
 * abstract query designer configurations into executable Doctrine QueryBuilder instances
 * with appropriate joins, conditions, and selections.
 */
interface QueryConverterExtensionInterface
{
    /**
     * @param AbstractQueryDesigner $source
     * @param QueryBuilder $queryBuilder
     * @return array
     */
    public function convert(AbstractQueryDesigner $source, QueryBuilder $queryBuilder);
}
