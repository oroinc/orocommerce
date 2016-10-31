<?php

namespace Oro\Bundle\PricingBundle\Expression;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Expression\QueryConverterExtensionInterface;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

class PriceListQueryConverterExtension implements QueryConverterExtensionInterface
{
    const TABLE_ALIAS_PREFIX = '_plt';

    /**
     * @var int
     */
    protected $tableSuffixCounter = 0;

    /**
     * @var array
     */
    protected $tableAliasByColumn = [];

    /**
     * {@inheritdoc}
     */
    public function convert(AbstractQueryDesigner $source, QueryBuilder $queryBuilder)
    {
        $this->tableAliasByColumn = [];
        $definition = json_decode($source->getDefinition(), JSON_OBJECT_AS_ARRAY);
        if (!empty($definition['price_lists'])) {
            $this->joinPriceLists($definition['price_lists'], $queryBuilder);
        }
        if (!empty($definition['prices'])) {
            $this->joinPriceListPrices($definition['prices'], $queryBuilder);
        }

        return $this->tableAliasByColumn;
    }

    /**
     * @param array|int[] $priceLists
     * @param QueryBuilder $queryBuilder
     */
    protected function joinPriceLists(array $priceLists, QueryBuilder $queryBuilder)
    {
        $aliases = $queryBuilder->getRootAliases();
        $rootAlias = reset($aliases);
        foreach ($priceLists as $priceListId) {
            $priceListId = (int)$priceListId;
            $columnAlias = $this->getPriceListTableKeyByPriceListId($priceListId);
            if (empty($this->tableAliasByColumn[$columnAlias])) {
                $priceListToProductTableAlias = $this->generateTableAlias();

                $priceListParameter = ':priceList' . $priceListId;
                $assignedProductJoin = $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($priceListToProductTableAlias . '.priceList', $priceListParameter),
                    $queryBuilder->expr()->eq($priceListToProductTableAlias . '.product', $rootAlias)
                );
                $queryBuilder->setParameter($priceListParameter, $priceListId);
                $queryBuilder->leftJoin(
                    PriceListToProduct::class,
                    $priceListToProductTableAlias,
                    Join::WITH,
                    $assignedProductJoin
                );

                $priceListTableAlias = $this->generateTableAlias();
                $queryBuilder->leftJoin(
                    PriceList::class,
                    $priceListTableAlias,
                    Join::WITH,
                    $queryBuilder->expr()
                        ->eq($priceListToProductTableAlias . '.priceList', $priceListTableAlias)
                );
                $this->tableAliasByColumn[$columnAlias] = $priceListTableAlias;
            }
        }
    }

    /**
     * @param array|int[] $priceLists
     * @param QueryBuilder $queryBuilder
     */
    protected function joinPriceListPrices(array $priceLists, QueryBuilder $queryBuilder)
    {
        $this->joinPriceLists($priceLists, $queryBuilder);

        $aliases = $queryBuilder->getRootAliases();
        $rootAlias = reset($aliases);
        foreach ($priceLists as $priceListId) {
            $priceListId = (int)$priceListId;
            $columnAlias = $this->getPriceTableKeyByPriceListId($priceListId);
            if (empty($this->tableAliasByColumn[$columnAlias])) {
                $priceListId = (int)$priceListId;
                $priceListTableKey =  $this->getPriceListTableKeyByPriceListId($priceListId);
                $priceListTableAlias = $this->tableAliasByColumn[$priceListTableKey];

                $priceTableAlias = $this->generateTableAlias();
                $joinCondition = $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($priceTableAlias . '.product', $rootAlias),
                    $queryBuilder->expr()->eq($priceTableAlias . '.priceList', $priceListTableAlias)
                );

                $queryBuilder->leftJoin(
                    ProductPrice::class,
                    $priceTableAlias,
                    Join::WITH,
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
     * @param int $priceListId
     * @return string
     */
    protected function getPriceListTableKeyByPriceListId($priceListId)
    {
        return PriceList::class . '|' . $priceListId;
    }

    /**
     * @return string
     */
    protected function generateTableAlias()
    {
        return self::TABLE_ALIAS_PREFIX . $this->tableSuffixCounter++;
    }
}
