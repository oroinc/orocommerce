<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver;

use Oro\Bundle\TaxBundle\Matcher\UnitedStatesHelper;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;

/**
 * Resolver to apply zero tax to digital kit product for US customers from states without digital product taxes
 */
class DigitalKitItemResolver implements ResolverInterface
{
    use CalculateUSSalesTaxTrait;

    public function resolve(Taxable $taxable): void
    {
        if (!$this->isApplicable($taxable)) {
            return;
        }

        $taxable->makeDestinationAddressTaxable();
        $this->calculateUnitPriceAndRowTotal($taxable);
    }

    private function isApplicable(Taxable $taxable): bool
    {
        $address = $taxable->getDestination();

        return $taxable->getPrice()->isPositive() &&
            $address &&
            !$taxable->getResult()->isResultLocked() &&
            $taxable->getContextValue(Taxable::DIGITAL_PRODUCT) &&
            UnitedStatesHelper::isStateWithoutDigitalTax($address->getCountryIso2(), $address->getRegionCode());
    }
}
