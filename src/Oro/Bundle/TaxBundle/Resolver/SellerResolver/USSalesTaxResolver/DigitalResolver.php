<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver;

use Oro\Bundle\TaxBundle\Matcher\UnitedStatesHelper;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;

/**
 * Resolver to apply zero tax to digital products for US customers from states without digital product taxes
 */
class DigitalResolver implements ResolverInterface
{
    public function __construct(
        private ResolverInterface $itemResolver
    ) {
    }

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
        $address = $taxable->getDestination();

        return $taxable->getItems()->count() &&
            !$taxable->isKitTaxable() &&
            !$taxable->getResult()->isResultLocked() &&
            $address &&
            UnitedStatesHelper::isStateWithoutDigitalTax($address->getCountryIso2(), $address->getRegionCode());
    }
}
