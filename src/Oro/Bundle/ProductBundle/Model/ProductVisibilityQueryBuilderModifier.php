<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\ORM\QueryBuilder;

class ProductVisibilityQueryBuilderModifier
{
    public function modifyByStatus(QueryBuilder $queryBuilder, array $productStatuses)
    {
        $this->addWhereInExpr($queryBuilder, 'status', $productStatuses);
    }

    public function modifyByInventoryStatus(QueryBuilder $queryBuilder, array $productInventoryStatuses)
    {
        $this->addWhereInExpr($queryBuilder, 'inventory_status', $productInventoryStatuses);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $field
     * @param array $in
     */
    protected function addWhereInExpr(QueryBuilder $queryBuilder, $field, array $in)
    {
        if (empty($in)) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        list($rootAlias) = $queryBuilder->getRootAliases();

        $parameterName = $field . '_' . $queryBuilder->getParameters()->count();

        $queryBuilder->andWhere($queryBuilder->expr()->in($rootAlias . '.' . $field, ':' . $parameterName))
            ->setParameter($parameterName, $in);
    }
}
