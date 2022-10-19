<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider\QuickAdd;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Provides quick add form import results from the inner providers.
 */
class QuickAddImportResultsProvider implements QuickAddImportResultsProviderInterface
{
    /** @var iterable<QuickAddImportResultsProviderInterface> */
    private iterable $innerProviders;

    public function __construct(iterable $innerProviders)
    {
        $this->innerProviders = $innerProviders;
    }

    public function getResults(QuickAddRowCollection $quickAddRowCollection): array
    {
        $results = [];
        foreach ($this->innerProviders as $innerProvider) {
            $results = ArrayUtil::arrayMergeRecursiveDistinct(
                $results,
                $innerProvider->getResults($quickAddRowCollection)
            );
        }

        return array_values($results);
    }
}
