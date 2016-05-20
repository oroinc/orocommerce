<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Form\DataTransformer\WeightTransformer;
use OroB2B\Bundle\ShippingBundle\Model\Weight;

class WeightTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WeightTransformer */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new WeightTransformer();
    }

    /**
     * @param Weight|null $value
     * @param Weight|null $expected
     *
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($value, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
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
     * @param Weight|null $value
     * @param Weight|null $expected
     *
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
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

    /**
     * @param string $code
     * @return WeightUnit
     */
    protected function getWeightUnit($code)
    {
        $weightUnit = new WeightUnit();
        $weightUnit->setCode($code);

        return $weightUnit;
    }

    /**
     * @param null|WeightUnit $weightUnit
     * @param float $value
     * @return Weight
     */
    protected function getWeight($weightUnit, $value)
    {
        return Weight::create($value, $weightUnit);
    }
}
