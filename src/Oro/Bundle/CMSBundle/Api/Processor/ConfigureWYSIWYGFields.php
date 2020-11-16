<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Configures WYSIWYG fields as nested objects with the following properties: "value", "style" and "properties".
 * If excludeWysiwygProperties is TRUE than the "properties" property is not added to nested objects.
 */
class ConfigureWYSIWYGFields implements ProcessorInterface
{
    /** @var WYSIWYGFieldsProvider */
    private $wysiwygFieldsProvider;

    /** @var bool */
    private $excludeWysiwygProperties;

    /**
     * @param WYSIWYGFieldsProvider $wysiwygFieldsProvider
     * @param bool                  $excludeWysiwygProperties
     */
    public function __construct(WYSIWYGFieldsProvider $wysiwygFieldsProvider, bool $excludeWysiwygProperties = false)
    {
        $this->wysiwygFieldsProvider = $wysiwygFieldsProvider;
        $this->excludeWysiwygProperties = $excludeWysiwygProperties;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();

        $wysiwygFields = $this->wysiwygFieldsProvider->getWysiwygFields($entityClass);
        if (empty($wysiwygFields)) {
            return;
        }

        $definition = $context->getResult();
        foreach ($wysiwygFields as $fieldName) {
            $field = $definition->findField($fieldName, true);
            if (null === $field) {
                $field = $definition->addField($fieldName);
            }
            $field->setDataType(DataType::NESTED_OBJECT);
            $field->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
            $field->setFormOption('inherit_data', true);

            $styleFieldName = $this->wysiwygFieldsProvider->getWysiwygStyleField($entityClass, $fieldName);
            $propertiesFieldName = $this->wysiwygFieldsProvider->getWysiwygPropertiesField($entityClass, $fieldName);

            $targetDefinition = $field->getOrCreateTargetEntity();
            $this->addNestedField($targetDefinition, 'value', $fieldName, DataType::STRING);
            $this->addNestedField($targetDefinition, 'style', $styleFieldName, DataType::STRING);
            if (!$this->excludeWysiwygProperties) {
                $this->addNestedField($targetDefinition, 'properties', $propertiesFieldName, DataType::OBJECT);
            }

            $this->excludeField($definition, $fieldName, '_');
            $this->excludeField($definition, $styleFieldName);
            $this->excludeField($definition, $propertiesFieldName);

            $field->setPropertyPath(null);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     * @param string                 $propertyPath
     * @param string                 $dataType
     */
    private function addNestedField(
        EntityDefinitionConfig $definition,
        string $fieldName,
        string $propertyPath,
        string $dataType
    ): void {
        $valueNestedField = $definition->addField($fieldName);
        $valueNestedField->setPropertyPath($propertyPath);
        $valueNestedField->setDataType($dataType);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $propertyPath
     * @param string|null            $fieldNamePrefix
     *
     * @return EntityDefinitionFieldConfig
     */
    private function excludeField(
        EntityDefinitionConfig $definition,
        string $propertyPath,
        string $fieldNamePrefix = null
    ): EntityDefinitionFieldConfig {
        $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
        if (!$fieldName) {
            $fieldName = $fieldNamePrefix . $propertyPath;
            $definition->addField($fieldName);
        }
        $field = $definition->getField($fieldName);
        if (!$field->hasExcluded()) {
            $field->setExcluded();
        }
        if (!$field->hasPropertyPath() && $fieldName !== $propertyPath) {
            $field->setPropertyPath($propertyPath);
        }

        return $field;
    }
}
