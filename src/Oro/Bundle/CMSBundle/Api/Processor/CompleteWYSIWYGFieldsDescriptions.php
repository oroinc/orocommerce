<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Adds human-readable descriptions for WYSIWYG fields.
 */
class CompleteWYSIWYGFieldsDescriptions implements ProcessorInterface
{
    /** @var WYSIWYGFieldsProvider */
    private $wysiwygFieldsProvider;

    /** @var FileLocatorInterface */
    private $fileLocator;

    /** @var array */
    private $descriptions = [];

    /**
     * @param WYSIWYGFieldsProvider $wysiwygFieldsProvider
     * @param FileLocatorInterface  $fileLocator
     */
    public function __construct(WYSIWYGFieldsProvider $wysiwygFieldsProvider, FileLocatorInterface $fileLocator)
    {
        $this->wysiwygFieldsProvider = $wysiwygFieldsProvider;
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $wysiwygFields = $this->wysiwygFieldsProvider->getWysiwygFields($context->getClassName());
        if (empty($wysiwygFields)) {
            return;
        }

        $definition = $context->getResult();
        foreach ($wysiwygFields as $fieldName) {
            $field = $this->getWysiwygField($definition, $fieldName);
            if (null === $field) {
                continue;
            }
            if ($field->hasDescription()) {
                continue;
            }
            if (!DataType::isNestedObject($field->getDataType())) {
                continue;
            }
            $targetDefinition = $field->getTargetEntity();
            if (null === $targetDefinition) {
                continue;
            }
            if ($this->isFieldNotExistOrExcluded($targetDefinition, 'value')) {
                continue;
            }
            if ($this->isFieldNotExistOrExcluded($targetDefinition, 'style')) {
                continue;
            }
            $hasPropertiesField = !$this->isFieldNotExistOrExcluded($targetDefinition, 'properties');
            $field->setDescription($this->getWysiwygFieldDescription($hasPropertiesField));
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $propertyPath
     *
     * @return EntityDefinitionFieldConfig|null
     */
    private function getWysiwygField(
        EntityDefinitionConfig $definition,
        string $propertyPath
    ): ?EntityDefinitionFieldConfig {
        $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
        if (0 === strncmp($fieldName, '_', 1)) {
            $fieldName = substr($fieldName, 1);
        }

        return $definition->getField($fieldName);
    }

    /**
     * @param bool $hasPropertiesField
     *
     * @return string
     */
    private function getWysiwygFieldDescription(bool $hasPropertiesField): string
    {
        $descriptionFile = $hasPropertiesField ? 'wysiwyg.md' : 'wysiwyg_without_properties.md';
        if (!isset($this->descriptions[$descriptionFile])) {
            $this->descriptions[$descriptionFile] = file_get_contents(
                $this->fileLocator->locate('@OroCMSBundle/Resources/doc/api/' . $descriptionFile)
            );
        }

        return $this->descriptions[$descriptionFile];
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $fieldName
     *
     * @return bool
     */
    private function isFieldNotExistOrExcluded(EntityDefinitionConfig $definition, string $fieldName): bool
    {
        $field = $definition->getField($fieldName);

        return null === $field || $field->isExcluded();
    }
}
