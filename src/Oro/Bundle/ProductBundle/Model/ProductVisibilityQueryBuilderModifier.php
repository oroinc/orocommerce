<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides method to apply restrictions by product status and product inventory status.
 */
class ProductVisibilityQueryBuilderModifier
{
    public function modifyByStatus(QueryBuilder $queryBuilder, array $productStatuses): void
    {
        $this->addWhereInExpr($queryBuilder, 'status', $productStatuses);
    }

    public function modifyByInventoryStatus(QueryBuilder $queryBuilder, array $productInventoryStatuses): void
    {
        $this->addWhereEnumInExpr($queryBuilder, 'inventory_status', $productInventoryStatuses);
    }

    private function addWhereInExpr(QueryBuilder $queryBuilder, string $field, array $in): void
    {
        if (!$in) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        [$rootAlias] = $queryBuilder->getRootAliases();
        $parameterName = $field . '_' . $queryBuilder->getParameters()->count();
        $queryBuilder->andWhere($queryBuilder->expr()->in($rootAlias . '.' . $field, ':' . $parameterName))
            ->setParameter($parameterName, $in);
    }

    private function addWhereEnumInExpr(QueryBuilder $queryBuilder, string $field, array $in): void
    {
        if (!$in) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        [$rootAlias] = $queryBuilder->getRootAliases();
        $parameterName = $field . '_' . $queryBuilder->getParameters()->count();
        $queryBuilder->andWhere($queryBuilder->expr()->in(
            QueryBuilderUtil::sprintf("JSON_EXTRACT(%s.serialized_data, '%s')", $rootAlias, $field),
            ':' . $parameterName
        ))->setParameter($parameterName, $in);
    }
}
