<?php

namespace Oro\Bundle\ShippingBundle\QueryDesigner;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverter;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class SelectQueryConverter extends GroupingOrmQueryConverter
{
    /**
     * @var array
     */
    protected $tableAliasByColumn = [];

    /**
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($functionProvider, $virtualFieldProvider, $doctrine);
    }

    /**
     * @param AbstractQueryDesigner $source
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function convert(AbstractQueryDesigner $source)
    {
        $this->qb = $this->doctrine->getManagerForClass($source->getEntity())->createQueryBuilder();
        $this->doConvert($source);

        return $this->qb;
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
