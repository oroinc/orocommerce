<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\ProductBundle\Entity\Product;

class CombinedPriceListToPriceListRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param array|Product[] $products
     * @return CombinedPriceListToPriceList[]
     */
    public function getPriceListRelations(CombinedPriceList $combinedPriceList, array $products = [])
    {
        $qb = $this->createQueryBuilder('combinedPriceListToPriceList');

        if ($products) {
            $qb
                ->innerJoin(
                    PriceListToProduct::class,
                    'priceListToProduct',
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq('priceListToProduct.priceList', 'combinedPriceListToPriceList.priceList'),
                        $qb->expr()->in('priceListToProduct.product', ':products')
                    )
                )
                ->setParameter('products', $products);
        }

        $qb->orderBy('combinedPriceListToPriceList.sortOrder')
            ->where($qb->expr()->eq('combinedPriceListToPriceList.combinedPriceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param PriceList[] $priceLists
     * @return BufferedQueryResultIteratorInterface
     */
    public function getCombinedPriceListsByActualPriceLists(array $priceLists)
    {
        $subQb = $qb = $this->getEntityManager()->createQueryBuilder();
        $subQb->select('1')
            ->from($this->getEntityName(), 'cpl2pl')
            ->innerJoin('cpl2pl.priceList', 'pl')
            ->where(
                $subQb->expr()->eq('pl.actual', ':isActual'),
                $subQb->expr()->eq('cpl2pl.combinedPriceList', 'cpl')
            );

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT cpl')
            ->from(CombinedPriceList::class, 'cpl')
            ->innerJoin(
                $this->getEntityName(),
                'priceListRelations',
                Join::WITH,
                $qb->expr()->eq('cpl', 'priceListRelations.combinedPriceList')
            )
            ->where(
                $qb->expr()->in('priceListRelations.priceList', ':priceLists'),
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $subQb->getDQL()
                    )
                )
            )
            ->setParameter('priceLists', $priceLists)
            ->setParameter('isActual', false);

        return new BufferedIdentityQueryResultIterator($qb->getQuery());
    }

    /**
     * @param CombinedPriceList[]|int[] $cpls
     * @return array
     */
    public function getPriceListIdsByCpls(array $cpls)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT IDENTITY(cpl2pl.priceList) as priceListId')
            ->from($this->getEntityName(), 'cpl2pl')
            ->where(
                $qb->expr()->in('cpl2pl.combinedPriceList', ':cpls')
            )
            ->setParameter('cpls', $cpls);

        return array_column($qb->getQuery()->getArrayResult(), 'priceListId');
    }
}
