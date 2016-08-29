<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;

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
     * @param PriceList $priceList
     * @return \Iterator
     */
    public function getProductsWithoutPrices(PriceList $priceList)
    {
        return new BufferedQueryResultIterator($this->getProductsWithoutPricesQueryBuilder($priceList));
    }

    /**
     * @param PriceList $priceList
     */
    public function deleteGeneratedRelations(PriceList $priceList)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(PriceListToProduct::class, 'pltp')
            ->where($qb->expr()->eq('pltp.priceList', ':priceList'))
            ->andWhere($qb->expr()->neq('pltp.manual', ':isManual'))
            ->setParameter('priceList', $priceList)
            ->setParameter('isManual', true);

        $qb->getQuery()->execute();
    }

    /**
     * @param PriceList $sourcePriceList
     * @param PriceList $targetPriceList
     * @param InsertFromSelectQueryExecutor $insertQueryExecutor
     * @internal param PriceList $priceList
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
