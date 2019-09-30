<?php

namespace Oro\Bundle\CMSBundle\Tools\DumperExtensions;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType as DBALWYSIWYGType;
use Oro\Bundle\CMSBundle\Helper\WYSIWYGSchemaHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * The extension for the entity config dumper that do the following:
 * adds field that are used to store WYSIWYG and WYSIWYG styles value.
 */
class WYSIWYGEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var WYSIWYGSchemaHelper
     */
    private $wysiwygSchemaHelper;

    /**
     * @var array
     */
    private $configs = [];

    /**
     * @param ConfigManager $configManager
     * @param WYSIWYGSchemaHelper $wysiwygSchemaHelper
     */
    public function __construct(ConfigManager $configManager, WYSIWYGSchemaHelper $wysiwygSchemaHelper)
    {
        $this->configManager = $configManager;
        $this->wysiwygSchemaHelper = $wysiwygSchemaHelper;
    }

    /**
     * @param string $actionType
     *
     * @return bool
     */
    public function supports($actionType): bool
    {
        return in_array($actionType, [
            ExtendConfigDumper::ACTION_PRE_UPDATE,
            ExtendConfigDumper::ACTION_POST_UPDATE
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(): void
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityConfigs = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('is_extend')) {
                continue;
            }

            $className = $entityConfig->getId()->getClassName();
            $fieldConfigs = $extendConfigProvider->getConfigs($className);
            foreach ($fieldConfigs as $fieldConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                $fieldType = $fieldConfigId->getFieldType();
                if (DBALWYSIWYGType::TYPE === $fieldType) {
                    $this->configs[] = ['entityConfig' => $entityConfig, 'fieldConfig' => $fieldConfig];
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdate(): void
    {
        foreach ($this->configs as $config) {
            $this->wysiwygSchemaHelper->createStyleField($config['entityConfig'], $config['fieldConfig']);
        }
    }
}
