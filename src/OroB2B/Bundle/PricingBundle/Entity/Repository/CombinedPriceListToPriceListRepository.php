<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

class CombinedPriceListToPriceListRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product|null $product
     * @return CombinedPriceListToPriceList[]
     */
    public function getPriceListRelations(CombinedPriceList $combinedPriceList, Product $product = null)
    {
        $qb = $this->createQueryBuilder('combinedPriceListToPriceList');

        if ($product) {
            $qb->innerJoin('combinedPriceListToPriceList.priceList', 'priceList')
                ->innerJoin(
                    'priceList.prices',
                    'prices',
                    Join::WITH,
                    $qb->expr()->eq('prices.product', ':product')
                )
                ->setParameter('product', $product);
        }

        $qb->orderBy('combinedPriceListToPriceList.sortOrder')
            ->where($qb->expr()->eq('combinedPriceListToPriceList.combinedPriceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList);

        return $qb->getQuery()->getResult();
    }
}
