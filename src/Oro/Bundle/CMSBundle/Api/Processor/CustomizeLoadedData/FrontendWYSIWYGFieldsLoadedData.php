<?php

namespace Oro\Bundle\CMSBundle\Api\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Group WYSIWYG additional fields in API on storefront
 */
class FrontendWYSIWYGFieldsLoadedData implements ProcessorInterface
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
        $wysiwygFields = $this->wysiwygFieldsProvider->getWysiwygFields($context->getClassName());

        if (empty($wysiwygFields)) {
            return;
        }

        $this->processWysiwygFields($context, $wysiwygFields);
    }

    /**
     * @param ContextInterface $context
     * @param array $wysiwygFields
     */
    private function processWysiwygFields(ContextInterface $context, array $wysiwygFields): void
    {
        /** @var CustomizeLoadedDataContext $context */
        $data = $context->getData();
        $wysiwygAttributes = $this->wysiwygFieldsProvider->getWysiwygAttributes($context->getClassName());

        foreach ($wysiwygFields as $fieldName) {
            foreach ($data as $key => &$item) {
                if (array_key_exists('productAttributes', $item)
                    && in_array($fieldName, $wysiwygAttributes, true)
                ) {
                    $wysiwygFieldName = $context->getResultFieldName($fieldName);
                    $item['productAttributes'][$fieldName] = $item[$wysiwygFieldName];
                    unset($item[$wysiwygFieldName]);
                    unset($item['productAttributes'][$fieldName]['properties']);
                } elseif (array_key_exists($fieldName, $item)) {
                    unset($item[$fieldName]['properties']);
                }
            }
        }

        $context->setData($data);
    }
}
