<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\OptionRowTransformer;
use OroB2B\Bundle\AttributeBundle\Form\Type\HiddenFallbackValueType;
use OroB2B\Bundle\AttributeBundle\Form\Type\OptionRowType;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;

class OptionRowTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param boolean $localized
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider transformDataProvider
     */
    public function testTransform($localized, $input, $expected)
    {
        $transformer = new OptionRowTransformer($localized);
        $this->assertEquals($expected, $transformer->transform($input));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        return [
            'not localized without data' => [
                'localized' => false,
                'input' => null,
                'expected' => [],
            ],
            'not localized with data' => [
                'localized' => false,
                'input' => [
                    OptionRowType::MASTER_OPTION_ID => null,
                    OptionRowType::ORDER => 5,
                    'data' => [
                        null => [
                            'value' => 'default value',
                            OptionRowType::IS_DEFAULT => true
                        ],
                        1 => [
                            'value' => new FallbackType(FallbackType::SYSTEM),
                            OptionRowType::IS_DEFAULT => false
                        ]
                    ]
                ],
                'expected' => [
                    OptionRowType::MASTER_OPTION_ID => null,
                    OptionRowType::ORDER => 5,
                    OptionRowType::DEFAULT_VALUE => 'default value',
                    OptionRowType::IS_DEFAULT => true,
                    OptionRowType::LOCALES => [
                        1 => new FallbackType(FallbackType::SYSTEM)
                    ]
                ],
            ],
            'localized without data' => [
                'localized' => true,
                'input' => null,
                'expected' => [],
            ],
            'localized with data' => [
                'localized' => true,
                'input' => [
                    OptionRowType::MASTER_OPTION_ID => null,
                    OptionRowType::ORDER => 5,
                    'data' => [
                        null => [
                            'value' => 'default value',
                            OptionRowType::IS_DEFAULT => true
                        ],
                        1 => [
                            'value' => new FallbackType(FallbackType::SYSTEM),
                            OptionRowType::IS_DEFAULT => false
                        ]
                    ]
                ],
                'expected' => [
                    OptionRowType::MASTER_OPTION_ID => null,
                    OptionRowType::ORDER => 5,
                    OptionRowType::DEFAULT_VALUE => 'default value',
                    OptionRowType::IS_DEFAULT => true,
                    OptionRowType::LOCALES => [
                        1 => [
                            HiddenFallbackValueType::FALLBACK_VALUE => new FallbackType(FallbackType::SYSTEM),
                            HiddenFallbackValueType::EXTEND_VALUE => false
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @param boolean $localized
     * @param mixed $input
     * @param mixed $expected
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($localized, $input, $expected)
    {
        $transformer = new OptionRowTransformer($localized);
        $this->assertEquals($expected, $transformer->reverseTransform($input));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            'not localized without data' => [
                'localized' => false,
                'input' => null,
                'expected' => null,
            ],
            'not localized with data' => [
                'localized' => false,
                'input' => [
                    OptionRowType::MASTER_OPTION_ID => null,
                    OptionRowType::ORDER => 5,
                    OptionRowType::DEFAULT_VALUE => 'default value',
                    OptionRowType::IS_DEFAULT => true,
                    OptionRowType::LOCALES => [
                        1 => new FallbackType(FallbackType::SYSTEM)
                    ]
                ],
                'expected' => [
                    OptionRowType::MASTER_OPTION_ID => null,
                    OptionRowType::ORDER => 5,
                    'data' => [
                        null => [
                            'value' => 'default value',
                            OptionRowType::IS_DEFAULT => true
                        ],
                        1 => [
                            'value' => new FallbackType(FallbackType::SYSTEM),
                            OptionRowType::IS_DEFAULT => false
                        ]
                    ]
                ]
            ],
            'localized without data' => [
                'localized' => true,
                'input' => null,
                'expected' => null,
            ],
            'localized with data' => [
                'localized' => true,
                'input' => [
                    OptionRowType::MASTER_OPTION_ID => null,
                    OptionRowType::ORDER => 5,
                    OptionRowType::DEFAULT_VALUE => 'default value',
                    OptionRowType::IS_DEFAULT => true,
                    OptionRowType::LOCALES => [
                        1 => [
                            HiddenFallbackValueType::FALLBACK_VALUE => new FallbackType(FallbackType::SYSTEM),
                            HiddenFallbackValueType::EXTEND_VALUE => false
                        ]
                    ]
                ],
                'expected' => [
                    OptionRowType::MASTER_OPTION_ID => null,
                    OptionRowType::ORDER => 5,
                    'data' => [
                        null => [
                            'value' => 'default value',
                            OptionRowType::IS_DEFAULT => true
                        ],
                        1 => [
                            'value' => new FallbackType(FallbackType::SYSTEM),
                            OptionRowType::IS_DEFAULT => false
                        ]
                    ]
                ]
            ],
        ];
    }
}
