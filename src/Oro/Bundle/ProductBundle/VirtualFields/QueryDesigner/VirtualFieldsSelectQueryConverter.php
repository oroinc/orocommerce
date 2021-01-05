<?php

namespace Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverter;

/**
 * Converts a virtual fields select query definition created by the query designer to an ORM query.
 */
class VirtualFieldsSelectQueryConverter extends GroupingOrmQueryConverter
{
    /** @var QueryBuilder */
    protected $qb;

    /**
     * @param AbstractQueryDesigner $source
     *
     * @return QueryBuilder
     */
    public function convert(AbstractQueryDesigner $source): QueryBuilder
    {
        $qb = $this->doctrine->getManagerForClass($source->getEntity())->createQueryBuilder();

        $this->qb = $qb;
        $this->doConvert($source);

        return $qb;
    }

    /**
     * {@inheritDoc}
     */
    protected function resetConvertState(): void
    {
        parent::resetConvertState();
        $this->qb = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function addFromStatement($entityClassName, $tableAlias)
    {
        $this->qb->from($entityClassName, $tableAlias);
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn($columnAlias, $columnSorting)
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByColumn($columnAlias)
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases($columnAliases)
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases($tableAliases)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function addSelectColumn(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $columnLabel,
        $functionExpr,
        $functionReturnType,
        $isDistinct = false
    ) {
        $this->qb->addSelect(sprintf('%s as %s', $columnExpr, $columnLabel));
    }

    /**
     * {@inheritdoc}
     */
    protected function addJoinStatement($joinType, $join, $joinAlias, $joinConditionType, $joinCondition)
    {
        if (self::LEFT_JOIN === $joinType) {
            $this->qb->leftJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        } else {
            $this->qb->innerJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        }
    }
}
