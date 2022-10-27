<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\FieldDescriptionUtil;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\CMSBundle\Api\Processor\CompleteWYSIWYGFieldsDescriptions;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CompleteWYSIWYGFieldsDescriptionsTest extends ConfigProcessorTestCase
{
    /** @var EntityDescriptionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityDescriptionProvider;

    /** @var FileLocatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileLocator;

    /** @var CompleteWYSIWYGFieldsDescriptions */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityDescriptionProvider = $this->createMock(EntityDescriptionProvider::class);
        $this->fileLocator = $this->createMock(FileLocatorInterface::class);

        $this->processor = new CompleteWYSIWYGFieldsDescriptions(
            $this->entityDescriptionProvider,
            $this->fileLocator
        );
    }

    private function expectLocateDescriptionFile(string $fileName): string
    {
        $filePath = realpath(__DIR__ . '/../../../../Resources/doc/api/' . $fileName);
        $this->fileLocator->expects(self::once())
            ->method('locate')
            ->with('@OroCMSBundle/Resources/doc/api/' . $fileName)
            ->willReturn($filePath);

        return file_get_contents($filePath);
    }

    public function testProcessWhenNoWysiwygFields(): void
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithDescription(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'description'   => 'Field 1',
                    'fields'        => [
                        'value'      => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'      => ['data_type' => 'string'],
                        'properties' => ['data_type' => 'object']
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescription(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'      => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'      => ['data_type' => 'string'],
                        'properties' => ['data_type' => 'object']
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $descriptionFileContent = $this->expectLocateDescriptionFile('wysiwyg_raw.md');
        $this->entityDescriptionProvider->expects(self::once())
            ->method('getFieldDocumentation')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(null);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['description'] = $descriptionFileContent;
        $this->assertConfig($expectedConfig, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionForUpdateAction(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'      => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'      => ['data_type' => 'string'],
                        'properties' => ['data_type' => 'object']
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $descriptionFileContent = $this->expectLocateDescriptionFile('wysiwyg_raw.md');
        $this->entityDescriptionProvider->expects(self::once())
            ->method('getFieldDocumentation')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(null);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setTargetAction(ApiAction::UPDATE);
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['description'] = $descriptionFileContent;
        $this->assertConfig($expectedConfig, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionAndWithFieldDocumentation(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'      => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'      => ['data_type' => 'string'],
                        'properties' => ['data_type' => 'object']
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];
        $fieldDocumentation = 'Field 1';

        $descriptionFileContent = $this->expectLocateDescriptionFile('wysiwyg_raw.md');
        $this->entityDescriptionProvider->expects(self::once())
            ->method('getFieldDocumentation')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn($fieldDocumentation);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['description'] = $fieldDocumentation . "\n\n" . $descriptionFileContent;
        $this->assertConfig($expectedConfig, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionAndWithRenderedValue(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'         => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'         => ['data_type' => 'string'],
                        'properties'    => ['data_type' => 'object'],
                        'valueRendered' => ['data_type' => 'string']
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $descriptionFileContent = $this->expectLocateDescriptionFile('wysiwyg.md');
        $this->entityDescriptionProvider->expects(self::once())
            ->method('getFieldDocumentation')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(null);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['description'] = $descriptionFileContent;
        $this->assertConfig($expectedConfig, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionAndWithRenderedValueForUpdateAction(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'         => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'         => ['data_type' => 'string'],
                        'properties'    => ['data_type' => 'object'],
                        'valueRendered' => ['data_type' => 'string']
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $descriptionFileContent = $this->expectLocateDescriptionFile('wysiwyg_for_update.md');
        $this->entityDescriptionProvider->expects(self::once())
            ->method('getFieldDocumentation')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(null);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setTargetAction(ApiAction::UPDATE);
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['description'] = $descriptionFileContent;
        $this->assertConfig($expectedConfig, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionAndWithExcludedRenderedValue(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'         => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'         => ['data_type' => 'string'],
                        'properties'    => ['data_type' => 'object'],
                        'valueRendered' => ['data_type' => 'string', 'exclude' => true]
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $descriptionFileContent = $this->expectLocateDescriptionFile('wysiwyg_raw.md');
        $this->entityDescriptionProvider->expects(self::once())
            ->method('getFieldDocumentation')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(null);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['description'] = $descriptionFileContent;
        $this->assertConfig($expectedConfig, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionAndWithExcludedValueField(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'      => ['data_type' => 'string', 'property_path' => 'field1', 'exclude' => true],
                        'style'      => ['data_type' => 'string'],
                        'properties' => ['data_type' => 'object']
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionAndWithExcludedStyleField(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'      => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'      => ['data_type' => 'string', 'exclude' => true],
                        'properties' => ['data_type' => 'object']
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionAndWithExcludedPropertiesField(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'      => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'      => ['data_type' => 'string'],
                        'properties' => ['data_type' => 'object', 'exclude' => true]
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessWhenWysiwygFieldDoesNotExistInConfig(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field2'  => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_',
                    'fields'        => [
                        'value'      => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'      => ['data_type' => 'string'],
                        'properties' => ['data_type' => 'object']
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionAndWithUnexpectedDataType(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1'  => [
                    'data_type'     => 'string',
                    'property_path' => '_',
                    'fields'        => [
                        'value'      => ['data_type' => 'string', 'property_path' => 'field1'],
                        'style'      => ['data_type' => 'string'],
                        'properties' => ['data_type' => 'object', 'exclude' => true]
                    ]
                ],
                '_field1' => [
                    'property_path' => 'field1'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessForWysiwygFieldWithoutDescriptionAndWithoutTargetFieldsConfig(): void
    {
        $config = [
            'wysiwyg_fields' => ['field1'],
            'fields'         => [
                'field1' => [
                    'data_type'     => 'nestedObject',
                    'property_path' => '_'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessForRenderedWysiwygFieldWithDescription(): void
    {
        $config = [
            'rendered_wysiwyg_fields' => ['field1' => ['field1', 'field1_style']],
            'fields'                  => [
                'field1' => [
                    'data_type'     => 'string',
                    'property_path' => '_',
                    'description'   => 'Field 1'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessForRenderedWysiwygFieldWithoutDescription(): void
    {
        $config = [
            'rendered_wysiwyg_fields' => ['field1' => ['field1', 'field1_style']],
            'fields'                  => [
                'field1' => [
                    'data_type'     => 'string',
                    'property_path' => '_'
                ]
            ]
        ];

        $descriptionFileContent = $this->expectLocateDescriptionFile('wysiwyg_rendered.md');
        $this->entityDescriptionProvider->expects(self::once())
            ->method('getFieldDocumentation')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(null);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['description'] = $descriptionFileContent;
        $this->assertConfig($expectedConfig, $this->context->getResult());
    }

    public function testProcessForRenderedWysiwygFieldWithoutDescriptionForUpdateAction(): void
    {
        $config = [
            'rendered_wysiwyg_fields' => ['field1' => ['field1', 'field1_style']],
            'fields'                  => [
                'field1' => [
                    'data_type'     => 'string',
                    'property_path' => '_'
                ]
            ]
        ];

        $descriptionFileContent = $this->expectLocateDescriptionFile('wysiwyg_rendered.md');
        $this->entityDescriptionProvider->expects(self::once())
            ->method('getFieldDocumentation')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn(null);

        $this->context->setResult($this->createConfigObject($config));
        $this->context->setTargetAction(ApiAction::UPDATE);
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['description'] =
            $descriptionFileContent . "\n\n" . FieldDescriptionUtil::MODIFY_READ_ONLY_FIELD_DESCRIPTION;
        $this->assertConfig($expectedConfig, $this->context->getResult());
    }

    public function testProcessForRenderedWysiwygFieldWithoutDescriptionAndWithFieldDocumentation(): void
    {
        $config = [
            'rendered_wysiwyg_fields' => ['field1' => ['field1', 'field1_style']],
            'fields'                  => [
                'field1' => [
                    'data_type'     => 'string',
                    'property_path' => '_'
                ]
            ]
        ];
        $fieldDocumentation = 'Field 1';

        $descriptionFileContent = $this->expectLocateDescriptionFile('wysiwyg_rendered.md');
        $this->entityDescriptionProvider->expects(self::once())
            ->method('getFieldDocumentation')
            ->with(self::TEST_CLASS_NAME, 'field1')
            ->willReturn($fieldDocumentation);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $expectedConfig = $config;
        $expectedConfig['fields']['field1']['description'] = $fieldDocumentation . "\n\n" . $descriptionFileContent;
        $this->assertConfig($expectedConfig, $this->context->getResult());
    }

    public function testProcessForNestedRenderedWysiwygFieldWithoutDescription(): void
    {
        $config = [
            'rendered_wysiwyg_fields' => ['field1.valueRendered' => ['field1', 'field1_style']],
            'fields'                  => [
                'field1' => [
                    'data_type'     => 'string',
                    'property_path' => '_'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testProcessWhenRenderedWysiwygFieldDoesNotExistInConfig(): void
    {
        $config = [
            'rendered_wysiwyg_fields' => ['field1' => ['field1', 'field1_style']],
            'fields'                  => [
                'field2' => [
                    'data_type'     => 'string',
                    'property_path' => '_'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }
}
