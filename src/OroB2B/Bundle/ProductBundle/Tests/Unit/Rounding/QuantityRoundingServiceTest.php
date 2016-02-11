<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Rounding;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Rounding\QuantityRoundingService;

class QuantityRoundingServiceTest extends AbstractRoundingServiceTest
{
    /** @var QuantityRoundingService */
    protected $service;

    /** {@inheritdoc} */
    protected function getRoundingService()
    {
        return new QuantityRoundingService($this->configManager);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testDefaultPrecision()
    {
        $this->service->round(15.1);
    }

    /**
     * @dataProvider roundQuantityProvider
     * @param string $roundingType
     * @param mixed $value
     * @param mixed $expectedValue
     * @param int $precision
     * @param ProductUnit $productUnit
     * @param Product $product
     */
    public function testRoundQuantity(
        $roundingType,
        $value,
        $expectedValue,
        $precision,
        ProductUnit $productUnit = null,
        Product $product = null
    ) {
        $this->prepareConfigManager($roundingType, $precision);

        $this->assertEquals($expectedValue, $this->service->roundQuantity($value, $productUnit, $product));
    }

    /**
     * @return array
     */
    public function roundQuantityProvider()
    {
        $unit = (new ProductUnit())->setDefaultPrecision(2)->setCode('kg');

        return [
            'no round without unit' => [
                'roundingType' => QuantityRoundingService::ROUND_HALF_UP,
                'value' => 5.5555,
                'expectedValue' => 5.5555,
                'precision' => 3,
            ],
            'default unit precision without product' => [
                'roundingType' => QuantityRoundingService::ROUND_HALF_UP,
                'value' => 5.5555,
                'expectedValue' => 5.56,
                'precision' => 2,
                'productUnit' => $unit,
            ],
            'no linked product unit' => [
                'roundingType' => QuantityRoundingService::ROUND_HALF_UP,
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
