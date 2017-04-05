<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;

trait ProductPriceReference
{
    /**
     * @param string $reference
     * @return ProductPrice
     */
    protected function getPriceByReference($reference)
    {
        $criteria = LoadProductPrices::$data[$reference];
        /** @var ProductPriceRepository $repository */
        $registry = $this->getContainer()->get('doctrine');
        $repository = $registry->getRepository(ProductPrice::class);
        /** @var Product $product */
        $criteria['product'] = $this->getReference($criteria['product']);
        if ($criteria['priceList'] === 'default_price_list') {
            $criteria['priceList'] = $registry->getManager()->getRepository('OroPricingBundle:PriceList')->getDefault();
        } else {
            /** @var PriceList $priceList */
            $criteria['priceList'] = $this->getReference($criteria['priceList']);
        }
        /** @var ProductUnit $unit */
        $criteria['unit'] = $this->getReference($criteria['unit']);
        unset($criteria['value']);
        $prices = $repository->findByPriceList(
            $this->getContainer()->get('oro_pricing.shard_manager'),
            $criteria['priceList'],
            $criteria
        );

        return $prices[0];
    }
}
