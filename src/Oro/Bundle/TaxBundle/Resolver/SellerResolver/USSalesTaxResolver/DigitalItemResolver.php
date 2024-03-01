<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver;

use Oro\Bundle\TaxBundle\Matcher\UnitedStatesHelper;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;

/**
 * Resolver to apply zero tax to digital product for US customers from states without digital product taxes
 */
class DigitalItemResolver implements ResolverInterface
{
    use CalculateUSSalesTaxTrait;

    public function __construct(
        private ResolverInterface $kitItemResolver
    ) {
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
        $address = $taxable->getDestination();

        return $taxable->getPrice()->isPositive() &&
            $address &&
            !$taxable->getResult()->isResultLocked() &&
            $taxable->getContextValue(Taxable::DIGITAL_PRODUCT) &&
            UnitedStatesHelper::isStateWithoutDigitalTax($address->getCountryIso2(), $address->getRegionCode());
    }
}
