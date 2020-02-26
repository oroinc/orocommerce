<?php

namespace Oro\Bundle\CMSBundle\Api\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Exclude WYSIWYG additional fields from API result
 */
class PrepareWYSIWYGAdditionalFields implements ProcessorInterface
{
    /**
     * @var WYSIWYGFieldsProvider
     */
    private $wysiwygFieldsProvider;

    /**
     * @param WYSIWYGFieldsProvider $wysiwygFieldsProvider
     */
    public function __construct(WYSIWYGFieldsProvider $wysiwygFieldsProvider)
    {
        $this->wysiwygFieldsProvider = $wysiwygFieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */
        $definition = $context->getResult();
        $entityClass = $context->getClassName();

        $wysiwygFields = $this->wysiwygFieldsProvider->getWysiwygFields($entityClass);

        if (empty($wysiwygFields)) {
            return;
        }

        $this->processWysiwygFields($definition, $entityClass, $wysiwygFields);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string $entityClass
     * @param array $wysiwygFields
     */
    private function processWysiwygFields(
        EntityDefinitionConfig $definition,
        string $entityClass,
        array $wysiwygFields
    ) {
        $wysiwygAttributes = $this->wysiwygFieldsProvider->getWysiwygAttributes($entityClass);

        foreach ($wysiwygFields as $fieldName) {
            $this->excludeAdditionalFields($definition, $wysiwygAttributes, $fieldName);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param array $wysiwygAttributes
     * @param string $fieldName
     */
    private function excludeAdditionalFields(
        EntityDefinitionConfig $definition,
        array $wysiwygAttributes,
        string $fieldName
    ): void {
        $styleFieldName = $fieldName . WYSIWYGStyleType::TYPE_SUFFIX;
        $propertiesFieldName = $fieldName . WYSIWYGPropertiesType::TYPE_SUFFIX;

        if ($definition->hasField($fieldName)) {
            $wysiwygField = $definition->getField($fieldName);
        } elseif (in_array($fieldName, $wysiwygAttributes, true)) {
            $wysiwygField = $definition->addField($fieldName);
            $wysiwygField->setDataType('string');
            $wysiwygField->setDependsOn(['serialized_data']);
        } else {
            return;
        }

        $this->addFieldIfNotExist($definition, $styleFieldName, 'string');
        $this->addFieldIfNotExist($definition, $propertiesFieldName, 'array');

        $excludedFields = [
            $definition->getField($styleFieldName),
            $definition->getField($propertiesFieldName)
        ];

        $wysiwygField->setDependsOn([
            $styleFieldName,
            $propertiesFieldName
        ]);

        foreach ($excludedFields as $excludedField) {
            if ($excludedField instanceof EntityDefinitionFieldConfig) {
                $excludedField->setExcluded();
            }
        }
    }

    private function addFieldIfNotExist(EntityDefinitionConfig $definition, string $fieldName, string $type): void
    {
        if (!$definition->hasField($fieldName)) {
            $field = $definition->addField($fieldName);
            $field->setDataType($type);
            $field->setDependsOn(['serialized_data']);
        }
    }
}
