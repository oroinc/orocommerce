<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CMSBundle\Api\WYSIWYGValueRenderer;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Moves WYSIWYG additional fields for WYSIWYG attributes to a specific attributes collection.
 */
class ComputeWYSIWYGAttributes implements ProcessorInterface
{
    private WYSIWYGFieldsProvider $wysiwygFieldsProvider;
    private WYSIWYGValueRenderer $wysiwygValueRenderer;
    private string $attributesFieldName;

    public function __construct(
        WYSIWYGFieldsProvider $wysiwygFieldsProvider,
        WYSIWYGValueRenderer $wysiwygValueRenderer,
        string $attributesFieldName
    ) {
        $this->wysiwygFieldsProvider = $wysiwygFieldsProvider;
        $this->wysiwygValueRenderer = $wysiwygValueRenderer;
        $this->attributesFieldName = $attributesFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        if (!$context->isFieldRequested($this->attributesFieldName)) {
            return;
        }

        $wysiwygAttributes = $this->wysiwygFieldsProvider->getWysiwygAttributes($context->getClassName());
        if (empty($wysiwygAttributes)) {
            return;
        }

        $renderedWysiwygFields = $this->getRenderedWysiwygFields($context);
        $data = $context->getData();
        foreach ($data as $key => $item) {
            foreach ($wysiwygAttributes as $fieldName) {
                $fieldName = $this->getWysiwygFieldName($context, $fieldName);
                $fieldValue = null;
                if (\array_key_exists($fieldName, $item)) {
                    $fieldValue = $item[$fieldName];
                } elseif ($renderedWysiwygFields && isset($renderedWysiwygFields[$fieldName])) {
                    [$valuePropertyName, $stylePropertyName] = $renderedWysiwygFields[$fieldName];
                    $fieldValue = $this->computeWysiwygAttributeValue(
                        $context,
                        $item,
                        $valuePropertyName,
                        $stylePropertyName
                    );
                }
                $data[$key][$this->attributesFieldName][$fieldName] = $fieldValue;
                unset($data[$key][$fieldName]);
            }
        }
        $context->setData($data);
    }

    private function getRenderedWysiwygFields(CustomizeLoadedDataContext $context): ?array
    {
        $config = $context->getConfig();

        return null !== $config
            ? ConfigureWYSIWYGFields::getRenderedWysiwygFields($config)
            : null;
    }

    private function getWysiwygFieldName(CustomizeLoadedDataContext $context, string $propertyPath): string
    {
        $fieldName = $context->getResultFieldName($propertyPath);
        if (0 === strncmp($fieldName, '_', 1)) {
            $fieldName = substr($fieldName, 1);
        }

        return $fieldName;
    }

    private function computeWysiwygAttributeValue(
        CustomizeLoadedDataContext $context,
        array $data,
        string $valuePropertyName,
        string $stylePropertyName
    ): ?string {
        $value = null;
        $valueFieldName = $context->getResultFieldName($valuePropertyName);
        if ($valueFieldName && isset($data[$valueFieldName])) {
            $value = $data[$valueFieldName];
        }
        $style = null;
        $styleFieldName = $context->getResultFieldName($stylePropertyName);
        if ($styleFieldName && isset($data[$styleFieldName])) {
            $style = $data[$styleFieldName];
        }

        return $this->wysiwygValueRenderer->render($value, $style);
    }
}
