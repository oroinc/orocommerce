<?php

namespace OroB2B\Bundle\PricingBundle\Query;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverter;

class PriceListExpressionQueryConverter extends GroupingOrmQueryConverter
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
     * @param AbstractQueryDesigner $source
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function convert(AbstractQueryDesigner $source)
    {
        /** @var array $definition */
        $definition = json_decode($source->getDefinition(), JSON_OBJECT_AS_ARRAY);
        if (!array_key_exists('columns', $definition)) {
            $definition['columns'] = [['name' => 'id']];
            $source->setDefinition($definition);
        }

        $this->qb = $this->doctrine->getManagerForClass($source->getEntity())->createQueryBuilder();
        $this->doConvert($source);

        return $this->qb;
    }

    /**
     * @return array
     */
    public function getTableAliasByColumn()
    {
        return $this->tableAliasByColumn;
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
        $this->qb->addSelect($columnExpr);
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
    protected function addJoinStatement($joinType, $join, $joinAlias, $joinConditionType, $joinCondition)
    {
        if (self::LEFT_JOIN === $joinType) {
            $this->qb->leftJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        } else {
            $this->qb->innerJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases($tableAliases)
    {
        foreach ($this->definition['columns'] as $column) {
            if (array_key_exists('table_identifier', $column)) {
                $columnName = $column['name'];
                $tableIdentifier = $column['table_identifier'];

                if (array_key_exists($columnName, $this->virtualColumnExpressions)) {
                    $exprColumn = explode('.', $this->virtualColumnExpressions[$columnName]);
                    $this->tableAliasByColumn[$tableIdentifier] = $exprColumn[0];
                } else {
                    $this->tableAliasByColumn[$tableIdentifier] = $this->getTableAliasForColumn($columnName);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereStatement()
    {
        // do nothing, conditions restrictions should be added in query compiler
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByColumn($columnAlias)
    {
        // do nothing, grouping is not allowed
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn($columnAlias, $columnSorting)
    {
        // do nothing, order could not change results
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases($columnAliases)
    {
        // do nothing
    }
}
