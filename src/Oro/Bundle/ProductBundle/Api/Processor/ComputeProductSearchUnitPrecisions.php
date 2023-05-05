<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "unitPrecisions" field for ProductSearch entity.
 */
class ComputeProductSearchUnitPrecisions implements ProcessorInterface
{
    private const UNIT_PRECISIONS_FIELD = 'unitPrecisions';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequested(self::UNIT_PRECISIONS_FIELD, $data)) {
            return;
        }

        $unitPrecisions = [];
        $units = $data['text_product_units'];
        if ($units) {
            $primaryUnitName = $data['text_primary_unit'];
            foreach ($units as $unitName => $precision) {
                $unitPrecisions[] = [
                    'unit'      => $unitName,
                    'precision' => $precision,
                    'default'   => $unitName === $primaryUnitName
                ];
            }
        }

        $data[self::UNIT_PRECISIONS_FIELD] = $unitPrecisions;

        $context->setData($data);
    }
}
