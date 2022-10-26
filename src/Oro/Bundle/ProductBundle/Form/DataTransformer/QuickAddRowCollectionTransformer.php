<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Form\DataTransformer;

use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Form data transformer for {@see QuickAddRowCollectionType}.
 */
class QuickAddRowCollectionTransformer implements DataTransformerInterface
{
    private QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder;

    public function __construct(QuickAddRowCollectionBuilder $quickAddRowCollectionBuilder)
    {
        $this->quickAddRowCollectionBuilder = $quickAddRowCollectionBuilder;
    }

    public function transform($value): ?array
    {
        $normalizedData = [];
        if ($value) {
            /** @var QuickAddRow $quickAddRow */
            foreach ($value as $quickAddRow) {
                $normalizedData[] = [
                    'productSku' => $quickAddRow->getSku(),
                    'productQuantity' => $quickAddRow->getQuantity(),
                    'productUnit' => $quickAddRow->getUnit(),
                ];
            }
        }

        return $normalizedData;
    }

    public function reverseTransform($value): QuickAddRowCollection
    {
        return $this->quickAddRowCollectionBuilder->buildFromArray((array)$value);
    }
}
