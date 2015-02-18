<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\OptionRowTransformer;
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
                    'master_option_id' => null,
                    'order' => 5,
                    'data' => [
                        null => [
                            'value' => 'default value',
                            'is_default' => true
                        ],
                        1 => [
                            'value' => new FallbackType(FallbackType::SYSTEM),
                            'is_default' => false
                        ]
                    ]
                ],
                'expected' => [
                    'master_option_id' => null,
                    'order' => 5,
                    'default' => 'default value',
                    'is_default' => true,
                    'locales' => [
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
                    'master_option_id' => null,
                    'order' => 5,
                    'data' => [
                        null => [
                            'value' => 'default value',
                            'is_default' => true
                        ],
                        1 => [
                            'value' => new FallbackType(FallbackType::SYSTEM),
                            'is_default' => false
                        ]
                    ]
                ],
                'expected' => [
                    'master_option_id' => null,
                    'order' => 5,
                    'default' => 'default value',
                    'is_default' => true,
                    'locales' => [
                        1 => [
                            'fallback_value' => new FallbackType(FallbackType::SYSTEM),
                            'extend_value' => false
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
                    'master_option_id' => null,
                    'order' => 5,
                    'default' => 'default value',
                    'is_default' => true,
                    'locales' => [
                        1 => new FallbackType(FallbackType::SYSTEM)
                    ]
                ],
                'expected' => [
                    'master_option_id' => null,
                    'order' => 5,
                    'data' => [
                        null => [
                            'value' => 'default value',
                            'is_default' => true
                        ],
                        1 => [
                            'value' => new FallbackType(FallbackType::SYSTEM),
                            'is_default' => false
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
                    'master_option_id' => null,
                    'order' => 5,
                    'default' => 'default value',
                    'is_default' => true,
                    'locales' => [
                        1 => [
                            'fallback_value' => new FallbackType(FallbackType::SYSTEM),
                            'extend_value' => false
                        ]
                    ]
                ],
                'expected' => [
                    'master_option_id' => null,
                    'order' => 5,
                    'data' => [
                        null => [
                            'value' => 'default value',
                            'is_default' => true
                        ],
                        1 => [
                            'value' => new FallbackType(FallbackType::SYSTEM),
                            'is_default' => false
                        ]
                    ]
                ]
            ],
        ];
    }
}
