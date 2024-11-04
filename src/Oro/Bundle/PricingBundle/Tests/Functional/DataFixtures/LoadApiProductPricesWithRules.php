<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

class LoadApiProductPricesWithRules extends LoadProductPricesWithRules
{
    #[\Override]
    public function getDependencies()
    {
        return [
            LoadProductUnitPrecisions::class,
            LoadApiPriceRules::class,
        ];
    }
}
