<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\DataTransformer;

use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\MultipleValueTransformer;

class MultipleValueTransformerTest extends \PHPUnit_Framework_TestCase
{
    const FIELD_DEFAULT = 'default';
    const FIELD_VALUES  = 'values';

    /**
     * @var MultipleValueTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new MultipleValueTransformer(self::FIELD_DEFAULT, self::FIELD_VALUES);
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
                'expected' => null,
            ],
            'no default' => [
                'input'    => [
                    1 => 'string',
                    2 => new FallbackType(FallbackType::SYSTEM),
                ],
                'expected' => [
                    self::FIELD_DEFAULT => null,
                    self::FIELD_VALUES => [
                        1 => 'string',
                        2 => new FallbackType(FallbackType::SYSTEM),
                    ]
                ],
            ],
            'with default' => [
                'input'    => [
                    null => 'default string',
                    1    => 'string',
                    2    => new FallbackType(FallbackType::SYSTEM),
                ],
                'expected' => [
                    self::FIELD_DEFAULT => 'default string',
                    self::FIELD_VALUES => [
                        1 => 'string',
                        2 => new FallbackType(FallbackType::SYSTEM),
                    ]
                ],
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
            'valid data' => [
                'input' => [
                    self::FIELD_DEFAULT => 'default string',
                    self::FIELD_VALUES => [
                        1 => 'string',
                        2 => new FallbackType(FallbackType::SYSTEM),
                    ]
                ],
                'expected'    => [
                    null => 'default string',
                    1    => 'string',
                    2    => new FallbackType(FallbackType::SYSTEM),
                ],
            ],
        ];
    }
}
