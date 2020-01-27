<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Helper;

use Oro\Bundle\CMSBundle\Helper\WYSIWYGSchemaHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

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

    public function testCreateSerializedFieldWhenNoSchema(): void
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
            ->with('schema', [])
            ->willReturnSelf();

        /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject $fieldConfig */
        $fieldConfig = $this->createMock(ConfigInterface::class);
        $fieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('is_serialized')
            ->willReturn(true);
        $fieldConfig
            ->expects($this->never())
            ->method('in');
        $fieldConfig
            ->expects($this->never())
            ->method('getId');

        $this->wysiwygSchemaHelper->createAdditionalFields($entityConfig, $fieldConfig);
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
            ->willReturn(['entity' => \stdClass::class]);
        $entityConfig
            ->expects($this->once())
            ->method('set')
            ->with('schema', $expected)
            ->willReturnSelf();

        $fieldConfigId = $this->createMock(FieldConfigId::class);
        $fieldConfigId
            ->expects($this->exactly(2))
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

        $this->wysiwygSchemaHelper->createAdditionalFields($entityConfig, $fieldConfig);
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
                        'field_name_style' => [],
                        'field_name_properties' => [],
                    ],
                    'entity' => \stdClass::class,
                ]
            ],
            'Updated field' => [
                'state' => ExtendScope::STATE_UPDATE,
                'expected' => [
                    'serialized_property' => [
                        'field_name_style' => [],
                        'field_name_properties' => [],
                    ],
                    'entity' => \stdClass::class,
                ]
            ],
            'New field' => [
                'state' => ExtendScope::STATE_NEW,
                'expected' => [
                    'serialized_property' => [
                        'field_name_style' => [],
                        'field_name_properties' => [],
                    ],
                    'entity' => \stdClass::class,
                ]
            ],
            'Restored field' => [
                'state' => ExtendScope::STATE_RESTORE,
                'expected' => [
                    'serialized_property' => [
                        'field_name_style' => [],
                        'field_name_properties' => [],
                    ],
                    'entity' => \stdClass::class,
                ]
            ],
            'Field deleted' => [
                'state' => ExtendScope::STATE_DELETE,
                'expected' => [
                    'serialized_property' => [
                        'field_name_style' => [
                            'private' => true
                        ],
                        'field_name_properties' => [
                            'private' => true
                        ],
                    ],
                    'entity' => \stdClass::class,
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
                    return ['entity' => TestActivityTarget::class];
                }
            });
        $entityConfig
            ->expects($this->once())
            ->method('set')
            ->with('schema', $expected)
            ->willReturnSelf();

        $fieldConfigId = $this->createMock(FieldConfigId::class);
        $fieldConfigId
            ->expects($this->exactly(2))
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

        $this->wysiwygSchemaHelper->createAdditionalFields($entityConfig, $fieldConfig);
    }

    /**
     * @return array
     */
    public function tableFieldStates(): array
    {
        $defaultState = [
            'entity' => TestActivityTarget::class,
            'property' => ['field_name_style' => [], 'field_name_properties' => []],
            'doctrine' => [
                TestActivityTarget::class => [
                    'fields' => [
                        'field_name_style' => [
                            'column' => 'field_name_style',
                            'type' => 'wysiwyg_style',
                            'nullable' => true,
                            'length' => null,
                            'precision' => null,
                            'scale' => null,
                            'default' => null,
                        ],
                        'field_name_properties' => [
                            'column' => 'field_name_properties',
                            'type' => 'wysiwyg_properties',
                            'nullable' => true,
                            'length' => null,
                            'precision' => null,
                            'scale' => null,
                            'default' => null,
                        ],
                    ]
                ]
            ]
        ];

        return [
            'Active field' => [
                'state' => ExtendScope::STATE_ACTIVE,
                'expected' => $defaultState
            ],
            'Updated field' => [
                'state' => ExtendScope::STATE_UPDATE,
                'expected' => $defaultState
            ],
            'New field' => [
                'state' => ExtendScope::STATE_NEW,
                'expected' => $defaultState
            ],
            'Restored field' => [
                'state' => ExtendScope::STATE_RESTORE,
                'expected' => $defaultState
            ],
            'Field deleted' => [
                'state' => ExtendScope::STATE_DELETE,
                'expected' => [
                    'entity' => TestActivityTarget::class,
                    'property' => [
                        'field_name_style' => ['private' => true],
                        'field_name_properties' => ['private' => true],
                    ],
                ]
            ]
        ];
    }
}
