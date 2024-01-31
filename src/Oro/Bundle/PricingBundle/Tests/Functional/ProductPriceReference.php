<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;

trait ProductPriceReference
{
    private function getPriceByReference(string $reference): ProductPrice
    {
        $criteria = LoadProductPrices::$data[$reference];
        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        /** @var ProductPriceRepository $productPriceRepository */
        $productPriceRepository = $doctrine->getRepository(ProductPrice::class);
        $criteria['product'] = $this->getReference($criteria['product']);
        $criteria['priceList'] = $this->getReference($criteria['priceList']);
        $criteria['unit'] = $this->getReference($criteria['unit']);
        unset($criteria['value']);
        $prices = $productPriceRepository->findByPriceList(
            self::getContainer()->get('oro_pricing.shard_manager'),
            $criteria['priceList'],
            $criteria
        );

        return $prices[0];
    }
}
