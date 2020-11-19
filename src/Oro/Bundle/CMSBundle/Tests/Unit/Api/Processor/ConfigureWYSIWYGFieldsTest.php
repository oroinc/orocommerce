<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\CMSBundle\Api\Processor\ConfigureWYSIWYGFields;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

class ConfigureWYSIWYGFieldsTest extends ConfigProcessorTestCase
{
    /** @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygFieldsProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);
    }

    /**
     * @param bool $excludeWysiwygProperties
     *
     * @return ConfigureWYSIWYGFields
     */
    private function getProcessor(bool $excludeWysiwygProperties = false): ConfigureWYSIWYGFields
    {
        return new ConfigureWYSIWYGFields($this->wysiwygFieldsProvider, $excludeWysiwygProperties);
    }

    public function testProcessWithoutWysiwygFields()
    {
        $config = [
            'fields' => [
                'someField' => null
            ]
        ];
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->getProcessor()->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
        self::assertFalse($this->context->has('_wysiwyg_fields'));
    }

    public function testProcess()
    {
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['wysiwygField']);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygStyleField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_style');
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygPropertiesField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_properties');
        $this->wysiwygFieldsProvider->expects(self::exactly(3))
            ->method('isSerializedWysiwygField')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'wysiwygField', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_style', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_properties', false]
            ]);

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField' => null
            ]
        ]));
        $this->getProcessor()->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'someField'               => null,
                    'wysiwygField'            => [
                        'data_type'     => 'nestedObject',
                        'form_options'  => [
                            'inherit_data' => true
                        ],
                        'property_path' => '_',
                        'depends_on'    => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'fields'        => [
                            'value'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties' => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField'
                    ],
                    'wysiwygField_style'      => [
                        'exclude' => true
                    ],
                    'wysiwygField_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
        self::assertEquals(['wysiwygField'], $this->context->get('_wysiwyg_fields'));
    }

    public function testProcessWithExcludeWysiwygProperties()
    {
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['wysiwygField']);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygStyleField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_style');
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygPropertiesField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_properties');
        $this->wysiwygFieldsProvider->expects(self::exactly(3))
            ->method('isSerializedWysiwygField')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'wysiwygField', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_style', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_properties', false]
            ]);

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField' => null
            ]
        ]));
        $this->getProcessor(true)->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'someField'               => null,
                    'wysiwygField'            => [
                        'data_type'     => 'nestedObject',
                        'form_options'  => [
                            'inherit_data' => true
                        ],
                        'property_path' => '_',
                        'depends_on'    => ['wysiwygField', 'wysiwygField_style'],
                        'fields'        => [
                            'value' => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style' => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField'
                    ],
                    'wysiwygField_style'      => [
                        'exclude' => true
                    ],
                    'wysiwygField_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
        self::assertEquals(['wysiwygField'], $this->context->get('_wysiwyg_fields'));
    }

    public function testProcessForRenamedWysiwygField()
    {
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['wysiwygField']);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygStyleField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_style');
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygPropertiesField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_properties');
        $this->wysiwygFieldsProvider->expects(self::exactly(3))
            ->method('isSerializedWysiwygField')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'wysiwygField', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_style', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_properties', false]
            ]);

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField'           => null,
                'renamedWysiwygField' => [
                    'property_path' => 'wysiwygField'
                ]
            ]
        ]));
        $this->getProcessor()->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'someField'               => null,
                    'renamedWysiwygField'     => [
                        'data_type'     => 'nestedObject',
                        'form_options'  => [
                            'inherit_data' => true
                        ],
                        'property_path' => '_',
                        'depends_on'    => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'fields'        => [
                            'value'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties' => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField'
                    ],
                    'wysiwygField_style'      => [
                        'exclude' => true
                    ],
                    'wysiwygField_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
        self::assertEquals(['wysiwygField'], $this->context->get('_wysiwyg_fields'));
    }

    public function testProcessForWysiwygFieldWithConfiguredFormOptions()
    {
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['wysiwygField']);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygStyleField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_style');
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygPropertiesField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_properties');
        $this->wysiwygFieldsProvider->expects(self::exactly(3))
            ->method('isSerializedWysiwygField')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'wysiwygField', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_style', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_properties', false]
            ]);

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField'    => null,
                'wysiwygField' => [
                    'form_options' => [
                        'mapped' => false
                    ]
                ]
            ]
        ]));
        $this->getProcessor()->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'someField'               => null,
                    'wysiwygField'            => [
                        'data_type'     => 'nestedObject',
                        'form_options'  => [
                            'mapped'       => false,
                            'inherit_data' => true
                        ],
                        'property_path' => '_',
                        'depends_on'    => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'fields'        => [
                            'value'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties' => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField'
                    ],
                    'wysiwygField_style'      => [
                        'exclude' => true
                    ],
                    'wysiwygField_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
        self::assertEquals(['wysiwygField'], $this->context->get('_wysiwyg_fields'));
    }

    public function testProcessForWysiwygFieldWithRenamedAdditionalFieldsAndOneOfThemIsMarkedAsNotExcluded()
    {
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['wysiwygField']);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygStyleField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_style');
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygPropertiesField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_properties');
        $this->wysiwygFieldsProvider->expects(self::exactly(3))
            ->method('isSerializedWysiwygField')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'wysiwygField', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_style', false],
                [self::TEST_CLASS_NAME, 'wysiwygField_properties', false]
            ]);

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField'                     => null,
                'renamedWysiwygFieldStyle'      => [
                    'property_path' => 'wysiwygField_style',
                    'exclude'       => false
                ],
                'renamedWysiwygFieldProperties' => [
                    'property_path' => 'wysiwygField_properties'
                ]
            ]
        ]));
        $this->getProcessor()->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'someField'                     => null,
                    'wysiwygField'                  => [
                        'data_type'     => 'nestedObject',
                        'form_options'  => [
                            'inherit_data' => true
                        ],
                        'property_path' => '_',
                        'depends_on'    => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'fields'        => [
                            'value'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties' => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ]
                        ]
                    ],
                    '_wysiwygField'                 => [
                        'property_path' => 'wysiwygField',
                        'exclude'       => true
                    ],
                    'renamedWysiwygFieldStyle'      => [
                        'property_path' => 'wysiwygField_style'
                    ],
                    'renamedWysiwygFieldProperties' => [
                        'property_path' => 'wysiwygField_properties',
                        'exclude'       => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
        self::assertEquals(['wysiwygField'], $this->context->get('_wysiwyg_fields'));
    }

    public function testProcessForSerializedWysiwygFields()
    {
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['wysiwygField']);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygStyleField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_style');
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygPropertiesField')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn('wysiwygField_properties');
        $this->wysiwygFieldsProvider->expects(self::exactly(3))
            ->method('isSerializedWysiwygField')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'wysiwygField', true],
                [self::TEST_CLASS_NAME, 'wysiwygField_style', true],
                [self::TEST_CLASS_NAME, 'wysiwygField_properties', true]
            ]);

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                'someField' => null
            ]
        ]));
        $this->getProcessor()->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'someField'               => null,
                    'wysiwygField'            => [
                        'data_type'     => 'nestedObject',
                        'form_options'  => [
                            'inherit_data' => true
                        ],
                        'property_path' => '_',
                        'depends_on'    => ['wysiwygField', 'wysiwygField_style', 'wysiwygField_properties'],
                        'fields'        => [
                            'value'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField'
                            ],
                            'style'      => [
                                'data_type'     => 'string',
                                'property_path' => 'wysiwygField_style'
                            ],
                            'properties' => [
                                'data_type'     => 'object',
                                'property_path' => 'wysiwygField_properties'
                            ]
                        ]
                    ],
                    '_wysiwygField'           => [
                        'exclude'       => true,
                        'property_path' => 'wysiwygField',
                        'depends_on'    => ['serialized_data']
                    ],
                    'wysiwygField_style'      => [
                        'exclude'    => true,
                        'depends_on' => ['serialized_data']
                    ],
                    'wysiwygField_properties' => [
                        'exclude'    => true,
                        'depends_on' => ['serialized_data']
                    ]
                ]
            ],
            $this->context->getResult()
        );
        self::assertEquals(['wysiwygField'], $this->context->get('_wysiwyg_fields'));
    }
}
