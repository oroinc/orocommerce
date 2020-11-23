<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Adds human-readable descriptions for WYSIWYG fields.
 */
class CompleteWYSIWYGFieldsDescriptions implements ProcessorInterface
{
    /** @var EntityDescriptionProvider */
    private $entityDescriptionProvider;

    /** @var FileLocatorInterface */
    private $fileLocator;

    /** @var array */
    private $descriptions = [];

    /**
     * @param EntityDescriptionProvider $entityDescriptionProvider
     * @param FileLocatorInterface      $fileLocator
     */
    public function __construct(
        EntityDescriptionProvider $entityDescriptionProvider,
        FileLocatorInterface $fileLocator
    ) {
        $this->entityDescriptionProvider = $entityDescriptionProvider;
        $this->fileLocator = $fileLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $wysiwygFields = ConfigureWYSIWYGFields::getWysiwygFields($context);
        if (empty($wysiwygFields)) {
            return;
        }

        $entityClass = $context->getClassName();
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
            $field->setDescription(
                $this->getWysiwygFieldDescription(
                    $entityClass,
                    $fieldName,
                    !$this->isFieldNotExistOrExcluded($targetDefinition, 'properties')
                )
            );
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
     * @param string $entityClass
     * @param string $fieldName
     * @param bool   $hasPropertiesField
     *
     * @return string
     */
    private function getWysiwygFieldDescription(
        string $entityClass,
        string $fieldName,
        bool $hasPropertiesField
    ): string {
        $descriptionFile = $hasPropertiesField ? 'wysiwyg.md' : 'wysiwyg_without_properties.md';
        if (!isset($this->descriptions[$descriptionFile])) {
            $this->descriptions[$descriptionFile] = file_get_contents(
                $this->fileLocator->locate('@OroCMSBundle/Resources/doc/api/' . $descriptionFile)
            );
        }

        $result = $this->descriptions[$descriptionFile];

        $fieldDescription = $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName);
        if ($fieldDescription) {
            $result = $fieldDescription . "\n\n" . $result;
        }

        return $result;
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
