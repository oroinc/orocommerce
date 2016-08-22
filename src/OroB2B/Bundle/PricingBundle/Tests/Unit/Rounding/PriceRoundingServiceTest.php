<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Rounding;

use OroB2B\Bundle\PricingBundle\Rounding\PriceRoundingService;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Rounding\AbstractRoundingServiceTest;

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
                            'oro_b2b_pricing.rounding_type',
                            PriceRoundingService::ROUND_HALF_UP,
                            false,
                            null,
                            $roundingType,
                        ],
                        [
                            'oro_b2b_pricing.precision',
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
