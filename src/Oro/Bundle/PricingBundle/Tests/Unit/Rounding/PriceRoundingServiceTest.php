<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Rounding;

use Oro\Bundle\PricingBundle\Rounding\PriceRoundingService;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Rounding\AbstractRoundingServiceTest;

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
        $this->configManager->expects($this->any())
            ->method('get')
            ->with($this->isType('string'))
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_pricing.rounding_type',
                            PriceRoundingService::ROUND_HALF_UP,
                            false,
                            null,
                            $roundingType,
                        ],
                        [
                            'oro_pricing.precision',
                            PriceRoundingService::FALLBACK_PRECISION,
                            false,
                            null,
                            $precision,
                        ],
                    ]
                )
            );
    }

    public function testEmptyConfigValues()
    {
        $this->prepareConfigManager(PriceRoundingService::ROUND_HALF_UP, PriceRoundingService::FALLBACK_PRECISION);

        $this->assertEquals(15.1235, $this->service->round(15.123456));
    }
}
