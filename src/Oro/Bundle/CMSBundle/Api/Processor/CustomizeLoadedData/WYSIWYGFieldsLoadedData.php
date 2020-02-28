<?php

namespace Oro\Bundle\CMSBundle\Api\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Group WYSIWYG additional fields in API
 */
class WYSIWYGFieldsLoadedData implements ProcessorInterface
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

        foreach ($wysiwygFields as $fieldName) {
            $wysiwygFieldName = $context->getResultFieldName($fieldName);
            if (array_key_exists($wysiwygFieldName, $data)) {
                $wysiwygValue = $data[$wysiwygFieldName];

                $wysiwygStyleFieldName = $context->getResultFieldName($fieldName . WYSIWYGStyleType::TYPE_SUFFIX);
                $wysiwygPropertiesFieldName = $context->getResultFieldName(
                    $fieldName . WYSIWYGPropertiesType::TYPE_SUFFIX
                );

                $wysiwygStyle = $data[$wysiwygStyleFieldName] ?? null;
                $wysiwygProperties = $data[$wysiwygPropertiesFieldName] ?? null;

                unset($data[$wysiwygStyleFieldName], $data[$wysiwygPropertiesFieldName]);

                $data[$fieldName] = [
                    'value' => $wysiwygValue,
                    'style' => $wysiwygStyle,
                    'properties' => $wysiwygProperties
                ];
            }
        }

        $context->setData($data);
    }
}
