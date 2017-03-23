<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

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

        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        return new BufferedIdentityQueryResultIterator($query);
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function deleteGeneratedRelations(PriceList $priceList, Product $product = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(PriceListToProduct::class, 'pltp')
            ->where($qb->expr()->eq('pltp.priceList', ':priceList'))
            ->andWhere($qb->expr()->neq('pltp.manual', ':isManual'))
            ->setParameter('priceList', $priceList)
            ->setParameter('isManual', true);

        if ($product) {
            $qb->andWhere($qb->expr()->eq('pltp.product', ':product'))
                ->setParameter('product', $product);
        }

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
}
