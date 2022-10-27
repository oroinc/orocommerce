<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Configures WYSIWYG fields in the following way:
 * * each WYSIWYG field converts a nested object field with the following properties:
 *   "value", "style", "properties" and "valueRendered".
 *   The "valueRendered" is a computed string field and its value is computed by
 *   {@see \Oro\Bundle\CMSBundle\Api\Processor\ComputeWYSIWYGFields}
 * * exclude source WYSIWYG fields
 */
class ConfigureCombinedWYSIWYGFields extends ConfigureWYSIWYGFields
{
    public const FIELD_VALUE_RENDERED = 'valueRendered';

    /**
     * {@inheritDoc}
     */
    protected function configureWysiwygField(
        ConfigContext $context,
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $fieldName,
        bool $excluded
    ): void {
        $wysiwygFieldName = $definition->findFieldNameByPropertyPath($fieldName) ?? $fieldName;
        if (self::isWysiwygFieldProcessed($definition, $wysiwygFieldName)) {
            return;
        }

        $wysiwygField = $this->createWysiwygField($definition, $wysiwygFieldName, $excluded);
        $this->addNestedValueField($wysiwygField, $fieldName);
        $this->addNestedStyleField($wysiwygField, $entityClass, $fieldName);
        $this->addNestedPropertiesField($wysiwygField, $entityClass, $fieldName);
        $this->addNestedRenderedValueField($wysiwygField, $entityClass, $fieldName, $definition, $wysiwygFieldName);
        self::registerWysiwygField($definition, $wysiwygFieldName);

        $this->configureSourceWysiwygFields($definition, $entityClass, $fieldName);
    }

    private function addNestedRenderedValueField(
        EntityDefinitionFieldConfig $wysiwygField,
        string $entityClass,
        string $fieldName,
        EntityDefinitionConfig $definition,
        string $wysiwygFieldName
    ): void {
        $this->addNestedField(
            $wysiwygField,
            self::FIELD_VALUE_RENDERED,
            ConfigUtil::IGNORE_PROPERTY_PATH,
            DataType::STRING
        );
        self::registerRenderedWysiwygField(
            $definition,
            $wysiwygFieldName . ConfigUtil::PATH_DELIMITER . self::FIELD_VALUE_RENDERED,
            $fieldName,
            $this->getWysiwygStyleFieldName($entityClass, $fieldName)
        );
    }
}
