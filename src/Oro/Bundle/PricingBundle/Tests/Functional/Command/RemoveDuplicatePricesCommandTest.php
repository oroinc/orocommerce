<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPricesWithDuplicates;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RemoveDuplicatePricesCommandTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadCombinedProductPricesWithDuplicates::class]);
    }

    public function testCommand(): void
    {
        /** @var CombinedPriceListGarbageCollector $gc */
        $gc = $this->getContainer()->get('oro_pricing.builder.combined_price_list_garbage_collector');
        self::assertTrue($gc->hasDuplicatePrices());
        $this->runCommand('oro:cron:prices:gc');
        self::assertFalse($gc->hasDuplicatePrices());
    }
}
