<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Form\DataTransformer\DimensionsTransformer;
use Oro\Bundle\ShippingBundle\Model\Dimensions;

class DimensionsTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DimensionsTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DimensionsTransformer();
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(Dimensions|string|null $value, ?Dimensions $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
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
     * @dataProvider transformDataProvider
     */
    public function testTransform(Dimensions|string|null $value, Dimensions|string|null $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
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

    private function getLengthUnit(string $code): LengthUnit
    {
        $lengthUnit = new LengthUnit();
        $lengthUnit->setCode($code);

        return $lengthUnit;
    }

    private function getDimensions(
        ?LengthUnit $lengthUnit,
        int|string|null $length,
        ?int $width,
        ?int $height
    ): Dimensions {
        return Dimensions::create($length, $width, $height, $lengthUnit);
    }
}
