<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Helper;

use Oro\Bundle\CMSBundle\Helper\WYSIWYGSchemaHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class WYSIWYGSchemaHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var WYSIWYGSchemaHelper */
    private $wysiwygSchemaHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->wysiwygSchemaHelper = new WYSIWYGSchemaHelper($this->configManager);
    }

    /**
     * @param string $state
     * @param array $expected
     *
     * @dataProvider serializedFieldStates
     */
    public function testCreateSerializedField(string $state, array $expected): void
    {
        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $entityConfig */
        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig
            ->expects($this->once())
            ->method('get')
            ->with('schema')
            ->willReturn([]);
        $entityConfig
            ->expects($this->once())
            ->method('set')
            ->with('schema', $expected)
            ->willReturnSelf();

        $fieldConfigId = $this->createMock(FieldConfigId::class);
        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn('field_name');

        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $fieldConfig */
        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('is_serialized')
            ->willReturn(true);
        $fieldConfig
            ->expects($this->once())
            ->method('in')
            ->with('state', [ExtendScope::STATE_DELETE])
            ->willReturnCallback(function ($arg1, $arg2) use ($state) {
                return in_array($state, $arg2);
            });
        $fieldConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        $this->wysiwygSchemaHelper->createStyleField($entityConfig, $fieldConfig);
    }

    /**
     * @return array
     */
    public function serializedFieldStates(): array
    {
        return [
            'Active field' => [
                'state' => ExtendScope::STATE_ACTIVE,
                'expected' => [
                    'serialized_property' => [
                        'field_name_style' => []
                    ]
                ]
            ],
            'Updated field' => [
                'state' => ExtendScope::STATE_UPDATE,
                'expected' => [
                    'serialized_property' => [
                        'field_name_style' => []
                    ]
                ]
            ],
            'New field' => [
                'state' => ExtendScope::STATE_NEW,
                'expected' => [
                    'serialized_property' => [
                        'field_name_style' => []
                    ]
                ]
            ],
            'Restored field' => [
                'state' => ExtendScope::STATE_RESTORE,
                'expected' => [
                    'serialized_property' => [
                        'field_name_style' => []
                    ]
                ]
            ],
            'Field deleted' => [
                'state' => ExtendScope::STATE_DELETE,
                'expected' => [
                    'serialized_property' => [
                        'field_name_style' => [
                            'private' => true
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param string $state
     * @param array $expected
     *
     * @dataProvider tableFieldStates
     */
    public function testCreateTableField(string $state, array $expected): void
    {
        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $entityConfig */
        $entityConfig = $this->createMock(ConfigInterface::class);
        $entityConfig
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($arg1) {
                if ('schema' === $arg1) {
                    return [];
                }

                if ('extend_class' === $arg1) {
                    return 'TestActivityTarget';
                }
            });
        $entityConfig
            ->expects($this->once())
            ->method('set')
            ->with('schema', $expected)
            ->willReturnSelf();

        $fieldConfigId = $this->createMock(FieldConfigId::class);
        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn('field_name');

        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $fieldConfig */
        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('is_serialized')
            ->willReturn(false);
        $fieldConfig
            ->expects($this->once())
            ->method('in')
            ->with('state', [ExtendScope::STATE_DELETE])
            ->willReturnCallback(function ($arg1, $arg2) use ($state) {
                return in_array($state, $arg2);
            });
        $fieldConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId);

        $this->wysiwygSchemaHelper->createStyleField($entityConfig, $fieldConfig);
    }

    /**
     * @return array
     */
    public function tableFieldStates(): array
    {
        return [
            'Active field' => [
                'state' => ExtendScope::STATE_ACTIVE,
                'expected' => [
                    'property' => ['field_name_style' => []],
                    'doctrine' => [
                        'TestActivityTarget' => [
                            'fields' => [
                                'field_name_style' => [
                                    'column' => 'field_name_style',
                                    'type' => 'wysiwyg_style',
                                    'nullable' => true,
                                    'length' => null,
                                    'precision' => null,
                                    'scale' => null,
                                    'default' => null,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'Updated field' => [
                'state' => ExtendScope::STATE_UPDATE,
                'expected' => [
                    'property' => ['field_name_style' => []],
                    'doctrine' => [
                        'TestActivityTarget' => [
                            'fields' => [
                                'field_name_style' => [
                                    'column' => 'field_name_style',
                                    'type' => 'wysiwyg_style',
                                    'nullable' => true,
                                    'length' => null,
                                    'precision' => null,
                                    'scale' => null,
                                    'default' => null,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'New field' => [
                'state' => ExtendScope::STATE_NEW,
                'expected' => [
                    'property' => ['field_name_style' => []],
                    'doctrine' => [
                        'TestActivityTarget' => [
                            'fields' => [
                                'field_name_style' => [
                                    'column' => 'field_name_style',
                                    'type' => 'wysiwyg_style',
                                    'nullable' => true,
                                    'length' => null,
                                    'precision' => null,
                                    'scale' => null,
                                    'default' => null,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'Restored field' => [
                'state' => ExtendScope::STATE_RESTORE,
                'expected' => [
                    'property' => ['field_name_style' => []],
                    'doctrine' => [
                        'TestActivityTarget' => [
                            'fields' => [
                                'field_name_style' => [
                                    'column' => 'field_name_style',
                                    'type' => 'wysiwyg_style',
                                    'nullable' => true,
                                    'length' => null,
                                    'precision' => null,
                                    'scale' => null,
                                    'default' => null,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'Field deleted' => [
                'state' => ExtendScope::STATE_DELETE,
                'expected' => [
                    'property' => ['field_name_style' => ['private' => true]],
                ]
            ]
        ];
    }
}
