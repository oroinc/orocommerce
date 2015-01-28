<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\DataTransfromer;

use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\FallbackValueTransformer;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;

class FallbackValueTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FallbackValueTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new FallbackValueTransformer();
    }

    /**
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider transformDataProvider
     */
    public function testTransform($input, $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($input));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            'null' => [
                'input'    => null,
                'expected' => ['value' => null, 'fallback' => null],
            ],
            'scalar' => [
                'input'    => 'string',
                'expected' => ['value' => 'string', 'fallback' => null],
            ],
            'fallback' => [
                'input'    => new FallbackType(FallbackType::SYSTEM),
                'expected' => ['value' => null, 'fallback' => FallbackType::SYSTEM],
            ],
        ];
    }

    /**
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($input, $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($input));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            'null' => [
                'input'    => null,
                'expected' => null,
            ],
            'empty array' => [
                'input'    => [],
                'expected' => null,
            ],
            'empty values' => [
                'input'    => ['value' => null, 'fallback' => null],
                'expected' => null,
            ],
            'scalar' => [
                'input'    => ['value' => 'string', 'fallback' => null],
                'expected' => 'string',
            ],
            'fallback' => [
                'expected' => ['value' => null, 'fallback' => FallbackType::SYSTEM],
                'input'    => new FallbackType(FallbackType::SYSTEM),
            ],
        ];
    }
}
