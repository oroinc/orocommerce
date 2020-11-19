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

        if (!$context->isFieldRequested($this->attributesFieldName)) {
            return;
        }

        $wysiwygAttributes = $this->wysiwygFieldsProvider->getWysiwygAttributes($context->getClassName());
        if (empty($wysiwygAttributes)) {
            return;
        }

        $data = $context->getData();
        foreach ($data as $key => $item) {
            foreach ($wysiwygAttributes as $fieldName) {
                $wysiwygFieldName = $this->getWysiwygFieldName($context, $fieldName);
                $data[$key][$this->attributesFieldName][$fieldName] = $item[$wysiwygFieldName];
                unset($data[$key][$wysiwygFieldName]);
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
