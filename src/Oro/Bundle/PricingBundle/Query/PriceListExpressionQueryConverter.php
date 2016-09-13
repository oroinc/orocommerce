<?php

namespace Oro\Bundle\PricingBundle\Query;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
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
        $this->tableAliasByColumn = [];
        /** @var array $definition */
        $definition = json_decode($source->getDefinition(), JSON_OBJECT_AS_ARRAY);
        if (empty($definition['columns'])) {
            $definition['columns'] = [['name' => 'id']];
            $source->setDefinition(json_encode($definition));
        }

        $this->qb = $this->doctrine->getManagerForClass($source->getEntity())->createQueryBuilder();
        $this->doConvert($source);

        if (!empty($definition['prices'])) {
            $this->joinPriceListPrices($definition['prices']);
        }
        if (!empty($definition['price_lists'])) {
            $this->joinPriceLists($definition['price_lists']);
        }

        return $this->qb;
    }

    /**
     * @param array|int[] $priceLists
     */
    protected function joinPriceLists(array $priceLists)
    {
        // Price list are joined through prices, join not joined prices
        $this->joinPriceListPrices($priceLists);

        foreach ($priceLists as $priceListId) {
            $columnAlias = PriceList::class . '|' . $priceListId;
            if (empty($this->tableAliasByColumn[$columnAlias])) {
                $priceListId = (int)$priceListId;

                $priceTableAlias = $this->tableAliasByColumn[$this->getPriceTableKeyByPriceListId($priceListId)];
                $priceListTableAlias = $this->generateTableAlias();
                $joinCondition = $this->qb->expr()->eq($priceTableAlias . '.priceList', $priceListTableAlias);

                $this->addJoinStatement(
                    self::LEFT_JOIN,
                    PriceList::class,
                    $priceListTableAlias,
                    self::CONDITIONAL_JOIN,
                    $joinCondition
                );
                $this->tableAliasByColumn[$columnAlias] = $priceListTableAlias;
            }
        }
    }

    /**
     * @param array|int[] $priceLists
     */
    protected function joinPriceListPrices(array $priceLists)
    {
        $aliases = $this->qb->getRootAliases();
        $rootAlias = reset($aliases);
        foreach ($priceLists as $priceListId) {
            $columnAlias = $this->getPriceTableKeyByPriceListId($priceListId);
            if (empty($this->tableAliasByColumn[$columnAlias])) {
                $priceListId = (int)$priceListId;

                $priceTableAlias = $this->generateTableAlias();
                $priceListParameter = ':priceList' . $priceListId;
                $joinCondition = $this->qb->expr()->andX(
                    $this->qb->expr()->eq($priceTableAlias . '.product', $rootAlias),
                    $this->qb->expr()->eq($priceTableAlias . '.priceList', $priceListParameter)
                );
                $this->qb->setParameter($priceListParameter, $priceListId);

                $this->addJoinStatement(
                    self::LEFT_JOIN,
                    ProductPrice::class,
                    $priceTableAlias,
                    self::CONDITIONAL_JOIN,
                    $joinCondition
                );
                $this->tableAliasByColumn[$columnAlias] = $priceTableAlias;
            }
        }
    }

    /**
     * @param int $priceListId
     * @return string
     */
    protected function getPriceTableKeyByPriceListId($priceListId)
    {
        return PriceList::class . '::prices|' . $priceListId;
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
