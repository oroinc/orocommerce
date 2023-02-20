<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Marks WYSIWYG attributes as excluded and adds them to "depends_on" option of a specific attributes collection.
 */
class ConfigureWYSIWYGAttributes implements ProcessorInterface
{
    private WYSIWYGFieldsProvider $wysiwygFieldsProvider;
    private string $attributesFieldName;

    public function __construct(WYSIWYGFieldsProvider $wysiwygFieldsProvider, string $attributesFieldName)
    {
        $this->wysiwygFieldsProvider = $wysiwygFieldsProvider;
        $this->attributesFieldName = $attributesFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();

        $attributesField = $definition->getField($this->attributesFieldName);
        if (null === $attributesField) {
            return;
        }

        $renderedWysiwygFields = ConfigureWYSIWYGFields::getRenderedWysiwygFields($definition);
        if (!$renderedWysiwygFields) {
            return;
        }

        $entityClass = $context->getClassName();
        foreach ($renderedWysiwygFields as $fieldName => [$valuePropertyName, $stylePropertyName]) {
            if ($this->wysiwygFieldsProvider->isWysiwygAttribute($entityClass, $valuePropertyName)) {
                $field = $definition->getField($fieldName);
                if (null !== $field) {
                    $field->setExcluded();
                    $attributesField->addDependsOn($valuePropertyName);
                    $attributesField->addDependsOn($stylePropertyName);
                }
            }
        }
    }
}
