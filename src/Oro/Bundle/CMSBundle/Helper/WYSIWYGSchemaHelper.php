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
        $schema = $entityConfig->get('schema', false, []);

        if (!$fieldConfig->is('is_serialized')) {
            $schema = $this->generateTableFieldSchema($schema, $fieldConfig);
        } elseif ($schema) {
            // Generates serialized fields schema only if schema for entity already exists.
            $schema = $this->generateSerializedSchema($schema, $fieldConfig);
        }

        $entityConfig->set('schema', $schema);
        $this->configManager->persist($entityConfig);
    }

    /**
     * @param array $schema
     * @param ConfigInterface $fieldConfig
     *
     * @return array
     */
    private function generateSerializedSchema(array $schema, ConfigInterface $fieldConfig): array
    {
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
     * @param array $schema
     * @param ConfigInterface $fieldConfig
     *
     * @return array
     */
    private function generateTableFieldSchema(array $schema, ConfigInterface $fieldConfig): array
    {
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
