<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Totals layout data provider.
 */
final class TotalsProvider
{
    public function __construct(private readonly TotalProcessorProvider $totalProcessorProvider)
    {
    }

    public function getTotalWithSubtotalsAsArray(object $entity): array
    {
        return $this->totalProcessorProvider->getTotalWithSubtotalsAsArray($entity);
    }
}
