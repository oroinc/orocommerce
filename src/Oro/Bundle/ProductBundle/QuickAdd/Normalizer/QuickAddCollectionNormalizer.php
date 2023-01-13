<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\QuickAdd\Normalizer;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * Normalizes {@see QuickAddRowCollection} using inner providers.
 */
class QuickAddCollectionNormalizer implements QuickAddCollectionNormalizerInterface
{
    /** @var iterable<QuickAddCollectionNormalizerInterface> */
    private iterable $innerNormalizers;

    public function __construct(iterable $innerNormalizers)
    {
        $this->innerNormalizers = $innerNormalizers;
    }

    public function normalize(QuickAddRowCollection $quickAddRowCollection): array
    {
        $result = ['errors' => [], 'items' => []];
        foreach ($this->innerNormalizers as $innerNormalizer) {
            $normalized = $innerNormalizer->normalize($quickAddRowCollection);
            $result['errors'][] = $normalized['errors'];
            foreach ($normalized['items'] as $index => $normalizedItem) {
                $result['items'][$index][] = $normalizedItem;
            }
        }

        $result['errors'] = array_merge(...$result['errors']);
        $result['items'] = array_values(array_map(
            static fn (array $normalizedItems) => array_merge(...$normalizedItems),
            $result['items']
        ));

        return $result;
    }
}
