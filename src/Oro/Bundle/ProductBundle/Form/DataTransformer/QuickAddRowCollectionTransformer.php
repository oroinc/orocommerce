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
                    QuickAddRow::SKU => $quickAddRow->getSku(),
                    QuickAddRow::QUANTITY => $quickAddRow->getQuantity(),
                    QuickAddRow::UNIT => $quickAddRow->getUnit(),
                    QuickAddRow::ORGANIZATION => $quickAddRow->getOrganization(),
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
