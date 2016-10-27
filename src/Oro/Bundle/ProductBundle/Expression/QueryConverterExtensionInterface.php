<?php

namespace Oro\Bundle\ProductBundle\Expression;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

interface QueryConverterExtensionInterface
{
    /**
     * @param AbstractQueryDesigner $source
     * @param QueryBuilder $queryBuilder
     * @return array
     */
    public function convert(AbstractQueryDesigner $source, QueryBuilder $queryBuilder);
}
