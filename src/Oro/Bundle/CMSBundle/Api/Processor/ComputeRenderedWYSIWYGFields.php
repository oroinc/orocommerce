<?php

namespace Oro\Bundle\CMSBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\CMSBundle\Api\WYSIWYGValueRenderer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of rendered WYSIWYG fields.
 * @see \Oro\Bundle\CMSBundle\Api\Processor\ConfigureWYSIWYGFields::getRenderedWysiwygFields
 */
class ComputeRenderedWYSIWYGFields implements ProcessorInterface
{
    private WYSIWYGValueRenderer $wysiwygValueRenderer;

    public function __construct(WYSIWYGValueRenderer $wysiwygValueRenderer)
    {
        $this->wysiwygValueRenderer = $wysiwygValueRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $renderedWysiwygFields = $this->getRenderedWysiwygFields($context);
        if (!$renderedWysiwygFields) {
            return;
        }

        $data = $context->getData();
        foreach ($renderedWysiwygFields as $fieldName => [$valuePropertyName, $stylePropertyName]) {
            $path = ConfigUtil::explodePropertyPath($fieldName);
            if ($context->isFieldRequested(reset($path))) {
                $renderedValue = $this->wysiwygValueRenderer->render(
                    $context->getResultFieldValue($valuePropertyName, $data),
                    $context->getResultFieldValue($stylePropertyName, $data)
                );
                if (null !== $renderedValue) {
                    $this->setDataValue($data, $path, $renderedValue);
                }
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

    /**
     * @param array    $data
     * @param string[] $path
     * @param string   $value
     */
    private function setDataValue(array &$data, array $path, string $value): void
    {
        $targetFieldName = array_pop($path);
        foreach ($path as $fieldName) {
            if (!isset($data[$fieldName])) {
                $data[$fieldName] = [];
            }
            $data = &$data[$fieldName];
        }
        $data[$targetFieldName] = $value;
    }
}
