<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Oro\Bundle\TaxBundle\Matcher\EuropeanUnionHelper;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;

/**
 * Resolver to switch taxation address to a customer's one for digital kit products
 */
class DigitalKitItemResolver extends AbstractItemResolver
{
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
        return $taxable->getPrice()->isPositive() &&
            $taxable->getDestination() &&
            !$taxable->getResult()->isResultLocked() &&
            $taxable->getContextValue(Taxable::DIGITAL_PRODUCT) &&
            EuropeanUnionHelper::isEuropeanUnionCountry($taxable->getDestination()->getCountryIso2());
    }
}
