<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\ApiDoc\EntityDescriptionProvider;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\FieldDescriptionUtil;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\CMSBundle\Api\Processor\ConfigureCombinedWYSIWYGFields as WYSIWYGFields;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Adds human-readable descriptions for WYSIWYG fields.
 */
class CompleteWYSIWYGFieldsDescriptions implements ProcessorInterface
{
    private EntityDescriptionProvider $entityDescriptionProvider;
    private FileLocatorInterface $fileLocator;
    private array $descriptions = [];

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
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        $entityClass = $context->getClassName();
        $targetAction = $context->getTargetAction();

        $wysiwygFields = ConfigureWYSIWYGFields::getWysiwygFields($definition);
        if ($wysiwygFields) {
            $this->processWysiwygFields($definition, $entityClass, $wysiwygFields, $targetAction);
        }
        $renderedWysiwygFields = ConfigureWYSIWYGFields::getRenderedWysiwygFields($definition);
        if ($renderedWysiwygFields) {
            $this->processRenderedWysiwygFields($definition, $entityClass, $renderedWysiwygFields, $targetAction);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string[]               $wysiwygFields
     * @param string|null            $targetAction
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processWysiwygFields(
        EntityDefinitionConfig $definition,
        string $entityClass,
        array $wysiwygFields,
        ?string $targetAction
    ): void {
        foreach ($wysiwygFields as $fieldName) {
            $field = $definition->getField($fieldName);
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
            if ($this->isFieldNotExistOrExcluded($targetDefinition, WYSIWYGFields::FIELD_VALUE)
                || $this->isFieldNotExistOrExcluded($targetDefinition, WYSIWYGFields::FIELD_STYLE)
                || $this->isFieldNotExistOrExcluded($targetDefinition, WYSIWYGFields::FIELD_PROPERTIES)
            ) {
                continue;
            }
            $field->setDescription($this->getWysiwygFieldDescription(
                $entityClass,
                $targetDefinition->getField(WYSIWYGFields::FIELD_VALUE)->getPropertyPath($fieldName),
                $targetAction,
                $this->isFieldNotExistOrExcluded($targetDefinition, WYSIWYGFields::FIELD_VALUE_RENDERED)
            ));
        }
    }

    private function processRenderedWysiwygFields(
        EntityDefinitionConfig $definition,
        string $entityClass,
        array $renderedWysiwygFields,
        ?string $targetAction
    ): void {
        foreach ($renderedWysiwygFields as $fieldName => $info) {
            if (str_contains($fieldName, ConfigUtil::PATH_DELIMITER)) {
                continue;
            }
            $field = $definition->getField($fieldName);
            if (null === $field) {
                continue;
            }
            if ($field->hasDescription()) {
                continue;
            }

            $field->setDescription($this->getRenderedWysiwygFieldDescription(
                $entityClass,
                $fieldName,
                $targetAction
            ));
        }
    }

    private function getWysiwygFieldDescription(
        string $entityClass,
        string $fieldName,
        ?string $targetAction,
        bool $isRaw
    ): string {
        $result = $this->loadDescriptionFile($this->getWysiwygFieldDescriptionFile($isRaw, $targetAction));

        $fieldDescription = $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName);
        if ($fieldDescription) {
            $result = $fieldDescription . "\n\n" . $result;
        }

        return $result;
    }

    private function getWysiwygFieldDescriptionFile(bool $isRaw, ?string $targetAction): string
    {
        if ($isRaw) {
            return 'wysiwyg_raw.md';
        }

        if (ApiAction::CREATE === $targetAction || ApiAction::UPDATE === $targetAction) {
            return 'wysiwyg_for_update.md';
        }

        return 'wysiwyg.md';
    }

    private function getRenderedWysiwygFieldDescription(
        string $entityClass,
        string $fieldName,
        ?string $targetAction
    ): string {
        $result = $this->loadDescriptionFile('wysiwyg_rendered.md');

        $fieldDescription = $this->entityDescriptionProvider->getFieldDocumentation($entityClass, $fieldName);
        if ($fieldDescription) {
            $result = $fieldDescription . "\n\n" . $result;
        }

        if (ApiAction::CREATE === $targetAction || ApiAction::UPDATE === $targetAction) {
            $result .= "\n\n" . FieldDescriptionUtil::MODIFY_READ_ONLY_FIELD_DESCRIPTION;
        }

        return $result;
    }

    private function loadDescriptionFile(string $descriptionFile): string
    {
        if (!isset($this->descriptions[$descriptionFile])) {
            $this->descriptions[$descriptionFile] = file_get_contents(
                $this->fileLocator->locate('@OroCMSBundle/Resources/doc/api/' . $descriptionFile)
            );
        }

        return $this->descriptions[$descriptionFile];
    }

    private function isFieldNotExistOrExcluded(EntityDefinitionConfig $definition, string $fieldName): bool
    {
        $field = $definition->getField($fieldName);

        return null === $field || $field->isExcluded();
    }
}
