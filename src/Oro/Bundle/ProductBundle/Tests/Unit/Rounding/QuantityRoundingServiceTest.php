<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Rounding;

use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Rounding\AbstractRoundingServiceTest;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;

class QuantityRoundingServiceTest extends AbstractRoundingServiceTest
{
    /** @var QuantityRoundingService */
    protected $service;

    /**
     * {@inheritdoc}
     */
    protected function getRoundingService(): AbstractRoundingService
    {
        return new QuantityRoundingService($this->configManager);
    }

    public function testDefaultPrecision()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->service->round(15.1);
    }

    /**
     * @dataProvider roundQuantityProvider
     */
    public function testRoundQuantity(
        int $roundingType,
        float $value,
        float $expectedValue,
        int $precision,
        ProductUnit $productUnit = null,
        Product $product = null
    ) {
        $this->prepareConfigManager($roundingType, $precision);

        $this->assertEquals($expectedValue, $this->service->roundQuantity($value, $productUnit, $product));
    }

    /**
     * @return array
     */
    public function roundQuantityProvider(): array
    {
        $unit = (new ProductUnit())->setDefaultPrecision(2)->setCode('kg');

        return [
            'no round without unit' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_UP,
                'value' => 5.5555,
                'expectedValue' => 5.5555,
                'precision' => 3,
            ],
            'default unit precision without product' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_UP,
                'value' => 5.5555,
                'expectedValue' => 5.56,
                'precision' => 2,
                'productUnit' => $unit,
            ],
            'no linked product unit' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_UP,
                'value' => 5.5555,
                'expectedValue' => 5.556,
                'precision' => 3,
                'productUnit' => $unit,
                'product' => (new Product())
                    ->addUnitPrecision((new ProductUnitPrecision())->setPrecision(3)->setUnit($unit)),
            ],
        ];
    }
}
