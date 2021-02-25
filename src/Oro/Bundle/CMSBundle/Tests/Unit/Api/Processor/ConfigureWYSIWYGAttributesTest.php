<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\CMSBundle\Api\Processor\ConfigureWYSIWYGAttributes;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

class ConfigureWYSIWYGAttributesTest extends ConfigProcessorTestCase
{
    private const ATTRIBUTES_FIELD_NAME = 'testAttributes';

    /** @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygFieldsProvider;

    /** @var ConfigureWYSIWYGAttributes */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);

        $this->processor = new ConfigureWYSIWYGAttributes(
            $this->wysiwygFieldsProvider,
            self::ATTRIBUTES_FIELD_NAME
        );
    }

    public function testProcessWithoutRenderedWysiwygFields()
    {
        $config = [
            'fields' => [
                self::ATTRIBUTES_FIELD_NAME => null,
                'someField'                 => null
            ]
        ];

        $this->wysiwygFieldsProvider->expects(self::never())
            ->method(self::anything());

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessWithoutWysiwygAttributes()
    {
        $config = [
            'rendered_wysiwyg_fields' => [
                'wysiwygField' => ['wysiwygField', 'wysiwygField_style']
            ],
            'fields'                  => [
                self::ATTRIBUTES_FIELD_NAME => null,
                'wysiwygField'              => null
            ]
        ];

        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('isWysiwygAttribute')
            ->with(self::TEST_CLASS_NAME, 'wysiwygField')
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcess()
    {
        $this->wysiwygFieldsProvider->expects(self::exactly(4))
            ->method('isWysiwygAttribute')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'wysiwygField1', true],
                [self::TEST_CLASS_NAME, 'wysiwygField2', true],
                [self::TEST_CLASS_NAME, 'wysiwygField3', true],
                [self::TEST_CLASS_NAME, 'wysiwygField4', false]
            ]);

        $renderedWysiwygFields = [
            'wysiwygField1'        => ['wysiwygField1', 'wysiwygField1_style'],
            'renamedWysiwygField2' => ['wysiwygField2', 'wysiwygField2_style'],
            'wysiwygField3'        => ['wysiwygField3', 'wysiwygField3_style'],
            'wysiwygField4'        => ['wysiwygField4', 'wysiwygField4_style']
        ];
        $this->context->setResult($this->createConfigObject([
            'rendered_wysiwyg_fields' => $renderedWysiwygFields,
            'fields'                  => [
                self::ATTRIBUTES_FIELD_NAME => null,
                'wysiwygField1'             => [
                    'depends_on' => ['wysiwygField1', 'wysiwygField1_style']
                ],
                '_wysiwygField1'            => [
                    'property_path' => 'wysiwygField1',
                    'exclude'       => true
                ],
                'wysiwygField1_style'       => [
                    'exclude' => true
                ],
                'wysiwygField1_properties'  => [
                    'exclude' => true
                ],
                'renamedWysiwygField2'      => [
                    'depends_on' => ['wysiwygField2', 'wysiwygField2_style']
                ],
                '_wysiwygField2'            => [
                    'property_path' => 'wysiwygField2',
                    'exclude'       => true
                ],
                'wysiwygField2Style'        => [
                    'property_path' => 'wysiwygField2_style',
                    'exclude'       => true
                ],
                'wysiwygField2Properties'   => [
                    'property_path' => 'wysiwygField2_properties',
                    'exclude'       => true
                ]
            ]
        ]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'rendered_wysiwyg_fields' => $renderedWysiwygFields,
                'fields'                  => [
                    self::ATTRIBUTES_FIELD_NAME => [
                        'depends_on' => [
                            'wysiwygField1',
                            'wysiwygField1_style',
                            'wysiwygField2',
                            'wysiwygField2_style'
                        ]
                    ],
                    'wysiwygField1'             => [
                        'depends_on' => ['wysiwygField1', 'wysiwygField1_style'],
                        'exclude'    => true
                    ],
                    '_wysiwygField1'            => [
                        'property_path' => 'wysiwygField1',
                        'exclude'       => true
                    ],
                    'wysiwygField1_style'       => [
                        'exclude' => true
                    ],
                    'wysiwygField1_properties'  => [
                        'exclude' => true
                    ],
                    'renamedWysiwygField2'      => [
                        'depends_on' => ['wysiwygField2', 'wysiwygField2_style'],
                        'exclude'    => true
                    ],
                    '_wysiwygField2'            => [
                        'property_path' => 'wysiwygField2',
                        'exclude'       => true
                    ],
                    'wysiwygField2Style'        => [
                        'property_path' => 'wysiwygField2_style',
                        'exclude'       => true
                    ],
                    'wysiwygField2Properties'   => [
                        'property_path' => 'wysiwygField2_properties',
                        'exclude'       => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
