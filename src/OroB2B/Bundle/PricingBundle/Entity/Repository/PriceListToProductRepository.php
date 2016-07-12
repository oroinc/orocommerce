<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;

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
            ->from('OroB2BProductBundle:Product', 'p')
            ->join(
                'OroB2BPricingBundle:PriceListToProduct',
                'plp',
                Join::WITH,
                $qb->expr()->eq('plp.product', 'p')
            )
            ->leftJoin(
                'OroB2BPricingBundle:ProductPrice',
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
}
