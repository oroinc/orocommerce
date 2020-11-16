<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Moves WYSIWYG additional fields for WYSIWYG attributes to a specific attributes collection.
 */
class ComputeWYSIWYGAttributes implements ProcessorInterface
{
    /** @var WYSIWYGFieldsProvider */
    private $wysiwygFieldsProvider;

    /** @var string */
    private $attributesFieldName;

    /**
     * @param WYSIWYGFieldsProvider $wysiwygFieldsProvider
     * @param string                $attributesFieldName
     */
    public function __construct(WYSIWYGFieldsProvider $wysiwygFieldsProvider, string $attributesFieldName)
    {
        $this->wysiwygFieldsProvider = $wysiwygFieldsProvider;
        $this->attributesFieldName = $attributesFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $wysiwygFields = $this->wysiwygFieldsProvider->getWysiwygFields($context->getClassName());
        if (empty($wysiwygFields)) {
            return;
        }

        $data = $context->getData();
        $wysiwygAttributes = $this->wysiwygFieldsProvider->getWysiwygAttributes($context->getClassName());
        foreach ($wysiwygFields as $fieldName) {
            foreach ($data as $key => $item) {
                if (\array_key_exists($this->attributesFieldName, $item)
                    && \in_array($fieldName, $wysiwygAttributes, true)
                ) {
                    $wysiwygFieldName = $this->getWysiwygFieldName($context, $fieldName);
                    $data[$key][$this->attributesFieldName][$fieldName] = $item[$wysiwygFieldName];
                    unset($data[$key][$wysiwygFieldName]);
                }
            }
        }
        $context->setData($data);
    }

    /**
     * @param CustomizeLoadedDataContext $context
     * @param string                     $propertyPath
     *
     * @return string
     */
    private function getWysiwygFieldName(CustomizeLoadedDataContext $context, string $propertyPath): string
    {
        $fieldName = $context->getResultFieldName($propertyPath);
        if (0 === strncmp($fieldName, '_', 1)) {
            $fieldName = substr($fieldName, 1);
        }

        return $fieldName;
    }
}
