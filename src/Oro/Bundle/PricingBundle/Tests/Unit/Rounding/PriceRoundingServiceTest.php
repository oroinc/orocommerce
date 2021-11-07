<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Rounding;

use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Rounding\AbstractRoundingServiceTest;
use Oro\Bundle\PricingBundle\Rounding\PriceRoundingService;

class PriceRoundingServiceTest extends AbstractRoundingServiceTest
{
    /**
     * {@inheritdoc}
     */
    protected function getRoundingService(): AbstractRoundingService
    {
        return new PriceRoundingService($this->configManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareConfigManager(string $roundingType, int $precision): void
    {
        $this->configManager->expects($this->atMost(2))
            ->method('get')
            ->with($this->isType('string'))
            ->willReturnMap([
                ['oro_pricing.rounding_type', false, false, null, $roundingType],
                ['oro_pricing.precision', false, false, null, $precision]
            ]);
    }

    public function testEmptyConfigValues()
    {
        $this->prepareConfigManager(RoundingServiceInterface::ROUND_HALF_UP, PriceRoundingService::FALLBACK_PRECISION);

        $this->assertEquals(15.1235, $this->service->round(15.123456));

        // check local cache
        $this->assertEquals(15.1235, $this->service->round(15.123456));
    }
}
