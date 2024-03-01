<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Oro\Bundle\TaxBundle\Matcher\EuropeanUnionHelper;
use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;
use Oro\Bundle\TaxBundle\Resolver\RowTotalResolver;
use Oro\Bundle\TaxBundle\Resolver\UnitResolver;

/**
 * Resolver to switch taxation address to a customer's one for digital products
 */
class DigitalItemResolver extends AbstractItemResolver
{
    public function __construct(
        UnitResolver $unitResolver,
        RowTotalResolver $rowTotalResolver,
        MatcherInterface $matcher,
        protected ResolverInterface $kitItemResolver
    ) {
        parent::__construct($unitResolver, $rowTotalResolver, $matcher);
    }

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
            $taxable->makeDestinationAddressTaxable();
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
            $taxable->getDestination() &&
            !$taxable->getResult()->isResultLocked() &&
            $taxable->getContextValue(Taxable::DIGITAL_PRODUCT) &&
            EuropeanUnionHelper::isEuropeanUnionCountry($taxable->getDestination()->getCountryIso2());
    }
}
