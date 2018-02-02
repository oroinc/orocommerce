<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
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
}
