<?php

namespace Oro\Bundle\CMSBundle\Api\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Add possibility to map grouped WYSIWYG additional fields in API
 */
class WYSIWYGFieldsFormData implements ProcessorInterface
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
     * @param ContextInterface $context
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
        /** @var CustomizeFormDataContext $context */
        $data = $context->getData();
        foreach ($wysiwygFields as $fieldName) {
            if (array_key_exists($fieldName, $data)) {
                $wysiwygValue = $data[$fieldName]['value'];
                $wysiwygStyle = $data[$fieldName]['style'];
                $wysiwygProperties = $data[$fieldName]['properties'];

                $data[$fieldName] = $wysiwygValue;
                $data[$fieldName . WYSIWYGStyleType::TYPE_SUFFIX] = $wysiwygStyle;
                $data[$fieldName . WYSIWYGPropertiesType::TYPE_SUFFIX] = $wysiwygProperties;
            }
        }

        $context->setData($data);
    }
}
