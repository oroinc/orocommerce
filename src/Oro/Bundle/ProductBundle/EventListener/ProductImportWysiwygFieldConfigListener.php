<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\AfterWriteFieldConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Listener for adding wysiwyg_style and wysiwyg_properties field models when import wysiwyg product attributes
 */
class ProductImportWysiwygFieldConfigListener
{
    private ConfigManager $configManager;
    private ConfigHelper $configHelper;

    public function __construct(ConfigManager $configManager, ConfigHelper $configHelper)
    {
        $this->configManager = $configManager;
        $this->configHelper  = $configHelper;
    }

    public function onAfterWriteFieldConfig(AfterWriteFieldConfigEvent $event): void
    {
        $fieldConfigModel = $event->getFieldConfigModel();
        $className = $fieldConfigModel->getEntity()->getClassName();
        $fieldName = $fieldConfigModel->getFieldName();
        $type = $fieldConfigModel->getType();
        $state = $this->getFieldModelState($fieldConfigModel);

        if ($className === Product::class && $type === WYSIWYGType::TYPE && $state !== ExtendScope::STATE_DELETE) {
            $this->createAdditionalWYSIWYGFieldModels(
                $className,
                $fieldName
            );
        }
    }

    private function createAdditionalWYSIWYGFieldModels(string $className, string $fieldName): void
    {
        $wysiwygFields = [
            WYSIWYGStyleType::TYPE_SUFFIX => WYSIWYGStyleType::TYPE,
            WYSIWYGPropertiesType::TYPE_SUFFIX => WYSIWYGPropertiesType::TYPE,
        ];

        $config = [
            'attribute' => [
                'is_attribute' => false,
            ],
            'extend' => [
                'is_extend' => true,
                'is_serialized' => true,
            ],
        ];

        foreach ($wysiwygFields as $suffix => $type) {
            if ($this->configManager->hasConfig($className, $fieldName . $suffix)) {
                continue;
            }

            $config['attribute']['field_name'] = $fieldName . $suffix;
            $newWysiwygFieldModel = $this->configManager->createConfigFieldModel(
                $className,
                $config['attribute']['field_name'],
                $type,
                ConfigModel::MODE_HIDDEN
            );

            $this->configHelper->updateFieldConfigs(
                $newWysiwygFieldModel,
                $config
            );
        }
    }

    private function getFieldModelState(FieldConfigModel $fieldConfigModel): string
    {
        $extendConfig = $fieldConfigModel->toArray('extend');

        return array_key_exists('state', $extendConfig) ? $extendConfig['state'] : ExtendScope::STATE_ACTIVE;
    }
}
