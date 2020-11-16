<?php

namespace commerce\src\Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor;

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

    public function testProcessWithoutWysiwygAttributes()
    {
        $config = [
            'fields' => [
                self::ATTRIBUTES_FIELD_NAME => null,
                'wysiwygField'              => null
            ]
        ];

        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygAttributes')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn([]);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcess()
    {
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygAttributes')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(['wysiwygField1', 'wysiwygField2', 'wysiwygField3']);

        $this->context->setResult($this->createConfigObject([
            'fields' => [
                self::ATTRIBUTES_FIELD_NAME => null,
                'wysiwygField1'             => [
                    'depends_on' => ['wysiwygField1', 'wysiwygField1_style', 'wysiwygField1_properties']
                ],
                '_wysiwygField1'            => [
                    'property_path' => 'wysiwygField1',
                    'exclude'       => true
                ],
                '_wysiwygField1_style'      => [
                    'exclude' => true
                ],
                '_wysiwygField1_properties' => [
                    'exclude' => true
                ],
                'wysiwygField2'             => [
                    'depends_on' => ['wysiwygField2', 'wysiwygField2_style', 'wysiwygField2_properties']
                ],
                '_wysiwygField2'            => [
                    'property_path' => 'wysiwygField2',
                    'exclude'       => true
                ],
                '_wysiwygField2_style'      => [
                    'exclude' => true
                ],
                '_wysiwygField2_properties' => [
                    'exclude' => true
                ]
            ]
        ]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    self::ATTRIBUTES_FIELD_NAME => [
                        'depends_on' => [
                            'wysiwygField1',
                            'wysiwygField1_style',
                            'wysiwygField1_properties',
                            'wysiwygField2',
                            'wysiwygField2_style',
                            'wysiwygField2_properties'
                        ]
                    ],
                    'wysiwygField1'             => [
                        'depends_on' => ['wysiwygField1', 'wysiwygField1_style', 'wysiwygField1_properties'],
                        'exclude'    => true
                    ],
                    '_wysiwygField1'            => [
                        'property_path' => 'wysiwygField1',
                        'exclude'       => true
                    ],
                    '_wysiwygField1_style'      => [
                        'exclude' => true
                    ],
                    '_wysiwygField1_properties' => [
                        'exclude' => true
                    ],
                    'wysiwygField2'             => [
                        'depends_on' => ['wysiwygField2', 'wysiwygField2_style', 'wysiwygField2_properties'],
                        'exclude'    => true
                    ],
                    '_wysiwygField2'            => [
                        'property_path' => 'wysiwygField2',
                        'exclude'       => true
                    ],
                    '_wysiwygField2_style'      => [
                        'exclude' => true
                    ],
                    '_wysiwygField2_properties' => [
                        'exclude' => true
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
