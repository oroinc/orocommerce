<?php

namespace Oro\Bundle\CMSBundle\Helper;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType as DBALWYSIWYGTypeStyle;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Helps create an additional style field for the wysiwyg field
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
    public function createStyleField(ConfigInterface $entityConfig, ConfigInterface $fieldConfig): void
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
        $styleFieldName = $this->getStyleFieldName($fieldConfig->getId());
        if ($fieldConfig->in('state', [ExtendScope::STATE_DELETE])) {
            $schema['serialized_property'][$styleFieldName]['private'] = true;
        } else {
            $schema['serialized_property'][$styleFieldName] = [];
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
        $className = $this->getExtendClassName($entityConfig);
        $styleFieldName = $this->getStyleFieldName($fieldConfig->getId());
        if ($fieldConfig->in('state', [ExtendScope::STATE_DELETE])) {
            $schema['property'][$styleFieldName]['private'] = true;
        } else {
            $schema['property'][$styleFieldName] = [];
            $schema['doctrine'][$className]['fields'][$styleFieldName] = [
                'column' => $styleFieldName,
                'type' => DBALWYSIWYGTypeStyle::TYPE,
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
     * @param ConfigInterface $config
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getExtendClassName(ConfigInterface $config): string
    {
        $extendClass = $config->get('extend_class', false, false);
        if (!$extendClass) {
            throw new \InvalidArgumentException('Option "extend_class" cannot be empty or null');
        }

        return $extendClass;
    }
}
