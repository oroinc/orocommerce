<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Debug\Provider;

use Oro\Bundle\PricingBundle\Debug\Provider\MinimalPricesSelectedPriceProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPricesForCombination;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MinimalPricesSelectedPriceProviderTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductPricesForCombination::class,
            LoadCombinedPriceLists::class
        ]);
    }

    public function testGetSelectedPricesIds()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('1t_2t_3t');
        /** @var Product $product */
        $product = $this->getReference('product-1');

        $relations = $this->getContainer()->get('doctrine')
            ->getRepository(CombinedPriceListToPriceList::class)
            ->getPriceListRelations($cpl);

        /** @var MinimalPricesSelectedPriceProvider $provider */
        $provider = $this->getContainer()->get('oro_pricing.tests.debug.minimal_prices_selected_price_provider');

        $expected = [
            $this->getReference('product_price.1')->getId(), // 1$/1l
            $this->getReference('product_price.2')->getId(), // 10$/9l
            $this->getReference('product_price.5')->getId(), // 3$/1b
            $this->getReference('product_price.8')->getId(), // 2EUR/1l
            $this->getReference('product_price.6')->getId()  // 15$/10l
        ];

        $this->assertEqualsCanonicalizing($expected, $provider->getSelectedPricesIds($relations, $product));
    }
}
