<?php

namespace Oro\Bundle\TaxBundle\Resolver\CustomerAddress;

use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;
use Oro\Bundle\TaxBundle\Resolver\RowTotalResolver;
use Oro\Bundle\TaxBundle\Resolver\UnitResolver;

/**
 * Resolve the unit price and row total for Taxable related to OrderLineItem.
 */
class CustomerAddressItemResolver extends AbstractItemResolver
{
    public function __construct(
        UnitResolver $unitResolver,
        RowTotalResolver $rowTotalResolver,
        MatcherInterface $matcher,
        protected ResolverInterface $kitItemResolver
    ) {
        parent::__construct($unitResolver, $rowTotalResolver, $matcher);
    }

    #[\Override]
    public function resolve(Taxable $taxable): void
    {
        if (!$this->isApplicable($taxable)) {
            return;
        }

        if ($taxable->isKitTaxable()) {
            $kitItemsResult = [];
            foreach ($taxable->getItems() as $kitItem) {
                $this->kitItemResolver->resolve($kitItem);
                $kitItemsResult[] = $kitItem->getResult();
            }

            $taxable->getResult()->offsetSet(Result::ITEMS, $kitItemsResult);
        }

        if ($this->isApplicableOrderLineItemTaxable($taxable)) {
            $this->calculateUnitPriceAndRowTotal($taxable);
        }
    }

    private function isApplicable(Taxable $taxable): bool
    {
        return !$taxable->getItems()->count() || ($taxable->getItems()->count() && $taxable->isKitTaxable());
    }

    private function isApplicableOrderLineItemTaxable(Taxable $taxable): bool
    {
        return $taxable->getPrice()->isPositive() &&
            $taxable->getTaxationAddress() &&
            !$taxable->getResult()->isResultLocked();
    }
}
