<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Form\DataTransformer\WeightTransformer;
use Oro\Bundle\ShippingBundle\Model\Weight;

class WeightTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WeightTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new WeightTransformer();
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(Weight|string|null $value, ?Weight $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        $weight = $this->getWeight($this->getWeightUnit('kg'), 2);

        return [
            'empty data' => [
                'value' => null,
                'expected' => null
            ],
            'full data' => [
                'value' => $weight,
                'expected' => $weight
            ],
            'bad data' => [
                'value' => $this->getWeight($this->getWeightUnit('kg'), 'bad'),
                'expected' => null
            ],
            'bad type' => [
                'value' => 'string',
                'expected' => null
            ],
            'empty code' => [
                'value' => $this->getWeight(null, 2),
                'expected' => null
            ],
            'empty value' => [
                'value' => $this->getWeight($this->getWeightUnit('kg'), null),
                'expected' => null
            ],
        ];
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(Weight|string|null $value, Weight|string|null $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        $weight = $this->getWeight($this->getWeightUnit('kg'), 2);

        return [
            'empty data' => [
                'value' => null,
                'expected' => null,
            ],
            'full data' => [
                'value' => $weight,
                'expected' => $weight,
            ],
            'bad data' => [
                'value' => $this->getWeight($this->getWeightUnit('kg'), 'bad'),
                'expected' => $this->getWeight($this->getWeightUnit('kg'), 'bad'),
            ],
            'bad type' => [
                'value' => 'string',
                'expected' => 'string',
            ],
        ];
    }

    private function getWeightUnit(string $code): WeightUnit
    {
        $weightUnit = new WeightUnit();
        $weightUnit->setCode($code);

        return $weightUnit;
    }

    private function getWeight(?WeightUnit $weightUnit, int|string|null $value): Weight
    {
        return Weight::create($value, $weightUnit);
    }
}
