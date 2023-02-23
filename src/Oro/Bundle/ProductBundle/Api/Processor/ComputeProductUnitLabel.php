<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of the following fields for ProductUnit entity:
 * * label
 * * shortLabel
 * * pluralLabel
 * * shortPluralLabel
 */
class ComputeProductUnitLabel implements ProcessorInterface
{
    private UnitLabelFormatterInterface $formatter;

    public function __construct(UnitLabelFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $productUnitCode = $context->getResultFieldValue('code', $data);

        $labelFieldName = $context->getResultFieldName('label');
        if ($context->isFieldRequested($labelFieldName, $data)) {
            $data[$labelFieldName] = $this->formatter->format($productUnitCode);
        }

        $shortLabelFieldName = $context->getResultFieldName('shortLabel');
        if ($context->isFieldRequested($shortLabelFieldName, $data)) {
            $data[$shortLabelFieldName] = $this->formatter->format($productUnitCode, true);
        }

        $pluralLabelFieldName = $context->getResultFieldName('pluralLabel');
        if ($context->isFieldRequested($pluralLabelFieldName, $data)) {
            $data[$pluralLabelFieldName] = $this->formatter->format($productUnitCode, false, true);
        }

        $shortPluralLabelFieldName = $context->getResultFieldName('shortPluralLabel');
        if ($context->isFieldRequested($shortPluralLabelFieldName, $data)) {
            $data[$shortPluralLabelFieldName] = $this->formatter->format($productUnitCode, true, true);
        }

        $context->setData($data);
    }
}
