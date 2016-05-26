<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Form\DataTransformer\DimensionsTransformer;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;

class DimensionsTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DimensionsTransformer */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new DimensionsTransformer();
    }

    /**
     * @param Dimensions|null $value
     * @param Dimensions|null $expected
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
        $dimension = $this->getDimensions($this->getLengthUnit('m'), 2, 4, 6);

        return [
            'empty data' => [
                'value' => null,
                'expected' => null
            ],
            'full data' => [
                'value' => $dimension,
                'expected' => $dimension
            ],
            'bad data' => [
                'value' => $this->getDimensions($this->getLengthUnit('m'), 'bad', 4, 6),
                'expected' => null
            ],
            'bad type' => [
                'value' => 'string',
                'expected' => null
            ],
            'empty code' => [
                'value' => $this->getDimensions(null, 2, 4, 6),
                'expected' => null
            ],
            'empty values' => [
                'value' => $this->getDimensions($this->getLengthUnit('m'), null, null, null),
                'expected' => null
            ]
        ];
    }

    /**
     * @param Dimensions|null $value
     * @param Dimensions|null $expected
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
        $dimension = $this->getDimensions($this->getLengthUnit('m'), 2, 4, 6);

        return [
            'empty data' => [
                'value' => null,
                'expected' => null,
            ],
            'full data' => [
                'value' => $dimension,
                'expected' => $dimension,
            ],
            'bad data' => [
                'value' => $this->getDimensions($this->getLengthUnit('m'), 'bad', 4, 6),
                'expected' => $this->getDimensions($this->getLengthUnit('m'), 'bad', 4, 6),
            ],
            'bad type' => [
                'value' => 'string',
                'expected' => 'string',
            ],
        ];
    }

    /**
     * @param string $code
     * @return LengthUnit
     */
    protected function getLengthUnit($code)
    {
        $lengthUnit = new LengthUnit();
        $lengthUnit->setCode($code);

        return $lengthUnit;
    }

    /**
     * @param null|LengthUnit $lengthUnit
     * @param float $length
     * @param float $width
     * @param float $height
     * @return Dimensions
     */
    protected function getDimensions($lengthUnit, $length, $width, $height)
    {
        return Dimensions::create($length, $width, $height, $lengthUnit);
    }
}
