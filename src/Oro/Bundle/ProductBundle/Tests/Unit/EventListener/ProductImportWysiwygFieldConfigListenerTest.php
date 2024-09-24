<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\AfterWriteFieldConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\ProductImportWysiwygFieldConfigListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductImportWysiwygFieldConfigListenerTest extends TestCase
{
    private ConfigManager|MockObject $configManager;
    private ConfigHelper|MockObject $configHelper;
    private ProductImportWysiwygFieldConfigListener $entityFieldModelWriteListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configHelper = $this->createMock(ConfigHelper::class);
        $this->entityFieldModelWriteListener = new ProductImportWysiwygFieldConfigListener(
            $this->configManager,
            $this->configHelper
        );
    }

    public function testOnAfterWriteFieldConfig(): void
    {
        $fieldName = 'testWysiwygField';
        $wysiwygItem = new FieldConfigModel($fieldName, WYSIWYGType::TYPE);
        $wysiwygStyleItem = new FieldConfigModel(
            $fieldName . WYSIWYGStyleType::TYPE_SUFFIX,
            WYSIWYGStyleType::TYPE
        );
        $wysiwygPropertiesItem = new FieldConfigModel(
            $fieldName . WYSIWYGPropertiesType::TYPE_SUFFIX,
            WYSIWYGPropertiesType::TYPE
        );
        $wysiwygItem->fromArray(
            'extend',
            [
                "owner" => "System",
                "state" => ExtendScope::STATE_NEW,
                "is_extend" => true,
                "is_deleted" => false,
                "nullable" => true,
                "is_serialized" => true,
                "immutable" => false,
            ]
        );
        $entityClassName = Product::class;
        $entityModel = new EntityConfigModel($entityClassName);
        $wysiwygItem->setEntity($entityModel);

        $this->configManager
            ->expects(self::exactly(2))
            ->method('hasConfig')
            ->withConsecutive(
                [$entityClassName, $fieldName . WYSIWYGStyleType::TYPE_SUFFIX],
                [$entityClassName, $fieldName . WYSIWYGPropertiesType::TYPE_SUFFIX]
            )
            ->willReturn(false, false);

        $this->configManager
            ->expects(self::exactly(2))
            ->method('createConfigFieldModel')
            ->withConsecutive(
                [
                    $entityClassName,
                    $fieldName . WYSIWYGStyleType::TYPE_SUFFIX,
                    WYSIWYGStyleType::TYPE,
                    ConfigModel::MODE_HIDDEN,
                ],
                [
                    $entityClassName,
                    $fieldName . WYSIWYGPropertiesType::TYPE_SUFFIX,
                    WYSIWYGPropertiesType::TYPE,
                    ConfigModel::MODE_HIDDEN,
                ]
            )
            ->willReturn($wysiwygStyleItem, $wysiwygPropertiesItem);

        $this->configHelper
            ->expects(self::exactly(2))
            ->method('updateFieldConfigs')
            ->withConsecutive(
                [
                    $wysiwygStyleItem,
                    [
                        'attribute' => [
                            'is_attribute' => false,
                            'field_name' => $fieldName . WYSIWYGStyleType::TYPE_SUFFIX,
                        ],
                        'extend' => [
                            'is_extend' => true,
                            'is_serialized' => true,
                        ],
                    ],
                ],
                [
                    $wysiwygPropertiesItem,
                    [
                        'attribute' => [
                            'is_attribute' => false,
                            'field_name' => $fieldName . WYSIWYGPropertiesType::TYPE_SUFFIX,
                        ],
                        'extend' => [
                            'is_extend' => true,
                            'is_serialized' => true,
                        ],
                    ],
                ]
            );

        $event = new AfterWriteFieldConfigEvent($wysiwygItem);

        $this->entityFieldModelWriteListener->onAfterWriteFieldConfig($event);
    }

    public function testOnAfterWriteFieldConfigWhenAdditionalFieldsExists(): void
    {
        $fieldName = 'testWysiwygField';
        $wysiwygItem = new FieldConfigModel($fieldName, WYSIWYGType::TYPE);
        $wysiwygItem->fromArray(
            'extend',
            [
                "owner" => "System",
                "state" => ExtendScope::STATE_NEW,
                "is_extend" => true,
                "is_deleted" => false,
                "nullable" => true,
                "is_serialized" => true,
                "immutable" => false,
            ]
        );
        $entityClassName = Product::class;
        $entityModel = new EntityConfigModel($entityClassName);
        $wysiwygItem->setEntity($entityModel);

        $this->configManager
            ->expects(self::exactly(2))
            ->method('hasConfig')
            ->withConsecutive(
                [$entityClassName, $fieldName . WYSIWYGStyleType::TYPE_SUFFIX],
                [$entityClassName, $fieldName . WYSIWYGPropertiesType::TYPE_SUFFIX]
            )
            ->willReturn(true, true);

        $this->configManager
            ->expects(self::never())
            ->method('createConfigFieldModel');

        $this->configHelper
            ->expects(self::never())
            ->method('updateFieldConfigs');

        $event = new AfterWriteFieldConfigEvent($wysiwygItem);

        $this->entityFieldModelWriteListener->onAfterWriteFieldConfig($event);
    }

    public function testOnAfterWriteFieldConfigWithNotWysiwygType(): void
    {
        $fieldName = 'testWysiwygField';
        $wysiwygItem = new FieldConfigModel($fieldName, 'text');

        $wysiwygItem->fromArray(
            'extend',
            [
                "owner" => "System",
                "state" => ExtendScope::STATE_NEW,
                "is_extend" => true,
                "is_deleted" => false,
                "nullable" => true,
                "is_serialized" => true,
                "immutable" => false,
            ]
        );
        $entityClassName = Product::class;
        $entityModel = new EntityConfigModel($entityClassName);
        $wysiwygItem->setEntity($entityModel);

        $this->configManager
            ->expects(self::never())
            ->method('hasConfig');

        $this->configManager
            ->expects(self::never())
            ->method('createConfigFieldModel');

        $this->configHelper
            ->expects(self::never())
            ->method('updateFieldConfigs');

        $event = new AfterWriteFieldConfigEvent($wysiwygItem);

        $this->entityFieldModelWriteListener->onAfterWriteFieldConfig($event);
    }

    public function testOnAfterWriteFieldConfigWithDeletedState(): void
    {
        $fieldName = 'testWysiwygField';
        $wysiwygItem = new FieldConfigModel($fieldName, WYSIWYGType::TYPE);

        $wysiwygItem->fromArray(
            'extend',
            [
                "owner" => "System",
                "state" => ExtendScope::STATE_DELETE,
                "is_extend" => true,
                "is_deleted" => false,
                "nullable" => true,
                "is_serialized" => true,
                "immutable" => false,
            ]
        );
        $entityClassName = Product::class;
        $entityModel = new EntityConfigModel($entityClassName);
        $wysiwygItem->setEntity($entityModel);

        $this->configManager
            ->expects(self::never())
            ->method('hasConfig');

        $this->configManager
            ->expects(self::never())
            ->method('createConfigFieldModel');

        $this->configHelper
            ->expects(self::never())
            ->method('updateFieldConfigs');

        $event = new AfterWriteFieldConfigEvent($wysiwygItem);

        $this->entityFieldModelWriteListener->onAfterWriteFieldConfig($event);
    }

    public function testOnAfterWriteFieldConfigWithNoProductEntity(): void
    {
        $fieldName = 'testWysiwygField';
        $wysiwygItem = new FieldConfigModel($fieldName, WYSIWYGType::TYPE);

        $wysiwygItem->fromArray(
            'extend',
            [
                "owner" => "System",
                "state" => ExtendScope::STATE_DELETE,
                "is_extend" => true,
                "is_deleted" => false,
                "nullable" => true,
                "is_serialized" => true,
                "immutable" => false,
            ]
        );
        $entityClassName = 'Test';
        $entityModel = new EntityConfigModel($entityClassName);
        $wysiwygItem->setEntity($entityModel);

        $this->configManager
            ->expects(self::never())
            ->method('hasConfig');

        $this->configManager
            ->expects(self::never())
            ->method('createConfigFieldModel');

        $this->configHelper
            ->expects(self::never())
            ->method('updateFieldConfigs');

        $event = new AfterWriteFieldConfigEvent($wysiwygItem);

        $this->entityFieldModelWriteListener->onAfterWriteFieldConfig($event);
    }
}
