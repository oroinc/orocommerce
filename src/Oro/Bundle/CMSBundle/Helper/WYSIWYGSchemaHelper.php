<?php

namespace Oro\Bundle\CMSBundle\Helper;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType as DBALWYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType as DBALWYSIWYGTypeStyle;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Helps create an additional fields for the wysiwyg field
 */
class WYSIWYGSchemaHelper
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param ConfigInterface $entityConfig
     * @param ConfigInterface $fieldConfig
     */
    public function createAdditionalFields(ConfigInterface $entityConfig, ConfigInterface $fieldConfig): void
    {
        if ($fieldConfig->is('is_serialized')) {
            $schema = $this->generateSerializedSchema($entityConfig, $fieldConfig);
        } else {
            $schema = $this->generateTableFieldSchema($entityConfig, $fieldConfig);
        }

        $entityConfig->set('schema', $schema);
        $this->configManager->persist($entityConfig);
    }

    /**
     * @param ConfigInterface $entityConfig
     * @param ConfigInterface $fieldConfig
     *
     * @return array
     */
    private function generateSerializedSchema(ConfigInterface $entityConfig, ConfigInterface $fieldConfig): array
    {
        $schema = $entityConfig->get('schema', false, []);
        $fieldConfigId = $fieldConfig->getId();
        $styleFieldName = $this->getStyleFieldName($fieldConfigId);
        $propertiesFieldName = $this->getPropertiesFieldName($fieldConfigId);
        if ($fieldConfig->in('state', [ExtendScope::STATE_DELETE])) {
            $schema['serialized_property'][$styleFieldName]['private'] = true;
            $schema['serialized_property'][$propertiesFieldName]['private'] = true;
        } else {
            $schema['serialized_property'][$styleFieldName] = [];
            $schema['serialized_property'][$propertiesFieldName] = [];
        }

        return $schema;
    }

    /**
     * @param ConfigInterface $entityConfig
     * @param ConfigInterface $fieldConfig
     *
     * @return array
     */
    private function generateTableFieldSchema(ConfigInterface $entityConfig, ConfigInterface $fieldConfig): array
    {
        $schema = $entityConfig->get('schema', false, []);
        $className = $schema['entity'];
        $fieldConfigId = $fieldConfig->getId();
        $styleFieldName = $this->getStyleFieldName($fieldConfigId);
        $propertiesFieldName = $this->getPropertiesFieldName($fieldConfigId);
        if ($fieldConfig->in('state', [ExtendScope::STATE_DELETE])) {
            $schema['property'][$styleFieldName]['private'] = true;
            $schema['property'][$propertiesFieldName]['private'] = true;
        } else {
            $schema['property'][$styleFieldName] = [];
            $schema['property'][$propertiesFieldName] = [];
            $schema['doctrine'][$className]['fields'][$styleFieldName] = [
                'column' => $styleFieldName,
                'type' => DBALWYSIWYGTypeStyle::TYPE,
                'nullable' => true,
                'length' => null,
                'precision' => null,
                'scale' => null,
                'default' => null
            ];
            $schema['doctrine'][$className]['fields'][$propertiesFieldName] = [
                'column' => $propertiesFieldName,
                'type' => DBALWYSIWYGPropertiesType::TYPE,
                'nullable' => true,
                'length' => null,
                'precision' => null,
                'scale' => null,
                'default' => null
            ];
        }

        return $schema;
    }

    /**
     * @param FieldConfigId|ConfigIdInterface $config
     *
     * @return string
     */
    private function getStyleFieldName(FieldConfigId $config): string
    {
        return $config->getFieldName() . DBALWYSIWYGTypeStyle::TYPE_SUFFIX;
    }

    /**
     * @param FieldConfigId|ConfigIdInterface $config
     *
     * @return string
     */
    private function getPropertiesFieldName(FieldConfigId $config): string
    {
        return $config->getFieldName() . DBALWYSIWYGPropertiesType::TYPE_SUFFIX;
    }
}
