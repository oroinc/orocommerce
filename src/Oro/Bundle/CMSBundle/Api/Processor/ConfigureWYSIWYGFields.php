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
    private const WYSIWYG_FIELDS = '_wysiwyg_fields';

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
     * Gets the list of names of WYSIWYG fields added by this processor.
     *
     * @param ConfigContext $context
     *
     * @return string[]|null
     */
    public static function getWysiwygFields(ConfigContext $context): ?array
    {
        return $context->get(self::WYSIWYG_FIELDS);
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
            $field = $this->createWysiwygField($definition, $fieldName);

            $valueField = $definition->getOrAddField('_' . $fieldName);
            if (!$valueField->hasPropertyPath()) {
                $valueField->setPropertyPath($fieldName);
            }

            $styleFieldName = $this->wysiwygFieldsProvider->getWysiwygStyleField($entityClass, $fieldName);
            $propertiesFieldName = $this->wysiwygFieldsProvider->getWysiwygPropertiesField($entityClass, $fieldName);

            $this->excludeField($definition, $entityClass, $fieldName);
            $this->excludeField($definition, $entityClass, $styleFieldName);
            $this->excludeField($definition, $entityClass, $propertiesFieldName);

            $this->addNestedField($field, 'value', $fieldName, DataType::STRING);
            $this->addNestedField($field, 'style', $styleFieldName, DataType::STRING);
            if (!$this->excludeWysiwygProperties) {
                $this->addNestedField($field, 'properties', $propertiesFieldName, DataType::OBJECT);
            }

            $this->addWysiwygFieldToContext($context, $fieldName);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     *
     * @return EntityDefinitionFieldConfig
     */
    private function createWysiwygField(
        EntityDefinitionConfig $definition,
        string $fieldName
    ): EntityDefinitionFieldConfig {
        $field = $definition->findField($fieldName, true);
        if (null === $field) {
            $field = $definition->addField($fieldName);
        }
        $field->setDataType(DataType::NESTED_OBJECT);
        $field->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $field->setFormOption('inherit_data', true);

        return $field;
    }

    /**
     * @param ConfigContext $context
     * @param string        $fieldName
     */
    private function addWysiwygFieldToContext(ConfigContext $context, string $fieldName): void
    {
        $wysiwygFields = $context->get(self::WYSIWYG_FIELDS) ?? [];
        $wysiwygFields[] = $fieldName;
        $context->set(self::WYSIWYG_FIELDS, $wysiwygFields);
    }

    /**
     * @param EntityDefinitionFieldConfig $wysiwygField
     * @param string                      $fieldName
     * @param string                      $propertyPath
     * @param string                      $dataType
     */
    private function addNestedField(
        EntityDefinitionFieldConfig $wysiwygField,
        string $fieldName,
        string $propertyPath,
        string $dataType
    ): void {
        $wysiwygField->addDependsOn($propertyPath);
        $valueNestedField = $wysiwygField->getOrCreateTargetEntity()->addField($fieldName);
        $valueNestedField->setPropertyPath($propertyPath);
        $valueNestedField->setDataType($dataType);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $propertyPath
     */
    private function excludeField(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $propertyPath
    ): void {
        $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
        if (!$fieldName) {
            $fieldName = $propertyPath;
            $definition->addField($fieldName);
        }
        /** @var EntityDefinitionFieldConfig $field */
        $field = $definition->getField($fieldName);
        if (!$field->hasExcluded()) {
            $field->setExcluded();
        }
        if (!$field->hasPropertyPath() && $fieldName !== $propertyPath) {
            $field->setPropertyPath($propertyPath);
        }
        if ($this->wysiwygFieldsProvider->isSerializedWysiwygField($entityClass, $propertyPath)) {
            $field->addDependsOn('serialized_data');
        }
    }
}
