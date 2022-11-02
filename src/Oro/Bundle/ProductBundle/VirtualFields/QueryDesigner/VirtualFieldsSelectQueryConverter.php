<?php

namespace Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryBuilderGroupingOrmQueryConverter;

/**
 * Converts a virtual fields select query definition created by the query designer to an ORM query.
 */
class VirtualFieldsSelectQueryConverter extends QueryBuilderGroupingOrmQueryConverter
{
    public function convert(AbstractQueryDesigner $source): QueryBuilder
    {
        $qb = $this->doctrineHelper->getEntityManagerForClass($source->getEntity())->createQueryBuilder();
        $this->context()->setQueryBuilder($qb);
        $this->doConvert($source);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn(string $columnAlias, string $columnSorting): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByColumn(string $columnAlias): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases(array $tableAliases): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases(array $columnAliases): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectColumn(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr,
        string $columnAlias,
        string $columnLabel,
        $functionExpr,
        ?string $functionReturnType,
        bool $isDistinct
    ): void {
        $this->context()->getQueryBuilder()->addSelect(sprintf('%s as %s', $columnExpr, $columnLabel));
    }
}
