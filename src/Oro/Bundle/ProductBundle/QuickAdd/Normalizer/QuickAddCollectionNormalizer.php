<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\QuickAdd\Normalizer;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Component\PhpUtils\ArrayUtil;

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
        $results = ['errors' => []];
        foreach ($this->innerNormalizers as $innerNormalizer) {
            $results = ArrayUtil::arrayMergeRecursiveDistinct(
                $results,
                $innerNormalizer->normalize($quickAddRowCollection)
            );
        }

        $results['items'] = array_values($results['items'] ?? []);

        return $results;
    }
}
