<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class PriceListToProductRepository extends EntityRepository
{
    /**
     * @param PriceList $priceList
     * @return QueryBuilder
     */
    public function getProductsWithoutPricesQueryBuilder(PriceList $priceList)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p')
            ->from('OroProductBundle:Product', 'p')
            ->join(
                'OroPricingBundle:PriceListToProduct',
                'plp',
                Join::WITH,
                $qb->expr()->eq('plp.product', 'p')
            )
            ->leftJoin(
                'OroPricingBundle:ProductPrice',
                'pp',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('pp.product', 'plp.product'),
                    $qb->expr()->eq('pp.priceList', 'plp.priceList')
                )
            )
            ->where($qb->expr()->isNull('pp.id'))
            ->andWhere($qb->expr()->eq('plp.priceList', ':priceList'))
            ->setParameter('priceList', $priceList);

        return $qb;
    }

    /**
     * @param ShardManager $shardManager
     * @param PriceList $priceList
     * @return \Iterator
     */
    public function getProductsWithoutPrices(ShardManager $shardManager, PriceList $priceList)
    {
        $query = $this->getProductsWithoutPricesQueryBuilder($priceList)
            ->getQuery();
        $query->useQueryCache(false);
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        return new BufferedIdentityQueryResultIterator($query);
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     * @return QueryBuilder
     */
    protected function getDeleteRelationsQueryBuilder(PriceList $priceList, array $products = []): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(PriceListToProduct::class, 'pltp')
            ->where($qb->expr()->eq('pltp.priceList', ':priceList'))
            ->setParameter('priceList', $priceList);

        if ($products) {
            $qb->andWhere($qb->expr()->in('pltp.product', ':products'))
                ->setParameter('products', $products);
        }

        return $qb;
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function deleteGeneratedRelations(PriceList $priceList, array $products = [])
    {
        $qb = $this->getDeleteRelationsQueryBuilder($priceList, $products);
        $qb->andWhere($qb->expr()->neq('pltp.manual', ':isManual'))
            ->setParameter('isManual', true);

        $qb->getQuery()->execute();
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    public function deleteManualRelations(PriceList $priceList, array $products = [])
    {
        $qb = $this->getDeleteRelationsQueryBuilder($priceList, $products);
        $qb->andWhere($qb->expr()->eq('pltp.manual', ':isManual'))
            ->setParameter('isManual', true);

        $qb->getQuery()->execute();
    }

    /**
     * @param PriceList $sourcePriceList
     * @param PriceList $targetPriceList
     * @param InsertFromSelectQueryExecutor $insertQueryExecutor
     */
    public function copyRelations(
        PriceList $sourcePriceList,
        PriceList $targetPriceList,
        InsertFromSelectQueryExecutor $insertQueryExecutor
    ) {
        $qb = $this->createQueryBuilder('priceListToProduct');
        $qb
            ->select(
                'IDENTITY(priceListToProduct.product)',
                (string)$qb->expr()->literal($targetPriceList->getId()),
                'priceListToProduct.manual'
            )
            ->where($qb->expr()->eq('priceListToProduct.priceList', ':sourcePriceList'))
            ->andWhere($qb->expr()->eq('priceListToProduct.manual', ':isManual'))
            ->setParameter('sourcePriceList', $sourcePriceList)
            ->setParameter('isManual', true);
        $fields = [
            'product',
            'priceList',
            'manual',
        ];

        $insertQueryExecutor->execute($this->getClassName(), $fields, $qb);
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     * @param bool $isManual
     * @return int
     */
    public function createRelation(PriceList $priceList, Product $product, $isManual = true)
    {
        $table = $this->getClassMetadata()->getTableName();

        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
        if ($platform instanceof MySqlPlatform) {
            $sql = sprintf('INSERT IGNORE INTO %s (price_list_id, product_id, is_manual) VALUES (?, ?, ?)', $table);
            $params = [
                $priceList->getId(),
                $product->getId(),
                $isManual
            ];
            $types = [
                Type::INTEGER,
                Type::INTEGER,
                Type::BOOLEAN
            ];
        } else {
            $sql = sprintf(
                'INSERT INTO %s (price_list_id, product_id, is_manual) SELECT ?, ?, ? WHERE NOT EXISTS (%s)',
                $table,
                sprintf('SELECT id FROM %s WHERE price_list_id = ? AND product_id = ? LIMIT ?', $table)
            );
            $params = [
                $priceList->getId(),
                $product->getId(),
                $isManual,
                $priceList->getId(),
                $product->getId(),
                1
            ];
            $types = [
                Type::INTEGER,
                Type::INTEGER,
                Type::BOOLEAN,
                Type::INTEGER,
                Type::INTEGER,
                Type::INTEGER,
            ];
        }

        return $this->getEntityManager()
            ->getConnection()
            ->executeUpdate($sql, $params, $types);
    }
}
