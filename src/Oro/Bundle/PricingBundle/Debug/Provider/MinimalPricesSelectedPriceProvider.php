<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Return price ids of prices chosen by the minimal strategy.
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class MinimalPricesSelectedPriceProvider implements SelectedPriceProviderInterface
{
    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    #[\Override]
    public function getSelectedPricesIds(array $priceListRelations, Product $product): array
    {
        $priceLists = array_map(
            fn (CombinedPriceListToPriceList $relation) => $relation->getPriceList(),
            $priceListRelations
        );

        $priceRepo = $this->registry->getRepository(ProductPrice::class);

        $qb = $priceRepo->getMinimalPriceIdsQueryBuilder($priceLists);
        $rootAlias = $qb->getRootAliases()[0];
        $qb->andWhere($qb->expr()->eq($rootAlias . '.product', ':product'))
            ->setParameter('product', $product);

        return $qb->getQuery()->getSingleColumnResult();
    }
}
