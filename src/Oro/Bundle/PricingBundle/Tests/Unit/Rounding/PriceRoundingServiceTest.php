<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Rounding;

use Oro\Bundle\CurrencyBundle\Tests\Unit\Rounding\AbstractRoundingServiceTest;
use Oro\Bundle\PricingBundle\Rounding\PriceRoundingService;

class PriceRoundingServiceTest extends AbstractRoundingServiceTest
{
    /** {@inheritdoc} */
    protected function getRoundingService()
    {
        return new PriceRoundingService($this->configManager);
    }

    /** {@inheritdoc} */
    protected function prepareConfigManager($roundingType, $precision)
    {
        $this->configManager->expects($this->atMost(2))
            ->method('get')
            ->with($this->isType('string'))
            ->willReturnMap(
                [
                    [
                        'oro_pricing.rounding_type',
                        false,
                        false,
                        null,
                        $roundingType,
                    ],
                    [
                        'oro_pricing.precision',
                        false,
                        false,
                        null,
                        $precision,
                    ],
                ]
            );
    }

    public function testEmptyConfigValues()
    {
        $this->prepareConfigManager(PriceRoundingService::ROUND_HALF_UP, PriceRoundingService::FALLBACK_PRECISION);

        $this->assertEquals(15.1235, $this->service->round(15.123456));

        // check local cache
        $this->assertEquals(15.1235, $this->service->round(15.123456));
    }
}
