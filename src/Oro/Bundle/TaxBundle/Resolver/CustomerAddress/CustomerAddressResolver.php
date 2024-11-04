<?php

namespace Oro\Bundle\TaxBundle\Resolver\CustomerAddress;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;

/**
 * Collect the resolved unit prices and row totals for Taxable.
 */
class CustomerAddressResolver implements ResolverInterface
{
    public function __construct(
        private CustomerAddressItemResolver $itemResolver
    ) {
    }

    #[\Override]
    public function resolve(Taxable $taxable): void
    {
        if (!$this->isApplicable($taxable)) {
            return;
        }

        $itemsResult = [];
        foreach ($taxable->getItems() as $taxableItem) {
            $this->itemResolver->resolve($taxableItem);
            $itemsResult[] = $taxableItem->getResult();
        }

        $taxable->getResult()->offsetSet(Result::ITEMS, $itemsResult);
    }

    private function isApplicable(Taxable $taxable): bool
    {
        return $taxable->getItems()->count() &&
            !$taxable->isKitTaxable() &&
            !$taxable->getResult()->isResultLocked();
    }
}
