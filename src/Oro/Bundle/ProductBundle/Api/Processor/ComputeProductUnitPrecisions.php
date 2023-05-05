<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "unitPrecisions" field for Product entity.
 */
class ComputeProductUnitPrecisions implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $unitPrecisionsFieldName = 'unitPrecisions';
        if (!$context->isFieldRequested($unitPrecisionsFieldName, $data)) {
            return;
        }

        $precisionsFieldName = $context->getResultFieldName($unitPrecisionsFieldName);
        $primaryPrecisionFieldName = $context->getResultFieldName('primaryUnitPrecision');

        $unitPrecisions = [];
        $defaultUnitId = $data[$primaryPrecisionFieldName]['id'] ?? null;
        foreach ($data[$precisionsFieldName] as $item) {
            if ($item['sell']) {
                $unitPrecisions[] = [
                    'unit'           => $item['unit']['id'],
                    'precision'      => $item['precision'],
                    'conversionRate' => $item['conversionRate'],
                    'default'        => $item['id'] === $defaultUnitId
                ];
            }
        }

        $data[$unitPrecisionsFieldName] = $unitPrecisions;
        $context->setData($data);
    }
}
