<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver;

use OroB2B\Bundle\TaxBundle\Matcher\UnitedStatesHelper;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface;

class USSalesTaxDigitalItemResolver implements ResolverInterface
{
    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        if ($taxable->getItems()->count()) {
            return;
        }

        if (!$taxable->getPrice()) {
            return;
        }

        $address = $taxable->getDestination();
        if (!$address) {
            return;
        }

        $isStateWithoutDigitalTax = UnitedStatesHelper::isStateWithoutDigitalTax(
            $address->getCountry()->getIso2Code(),
            $address->getRegion()->getCode()
        );

        if ($isStateWithoutDigitalTax && $taxable->getContextValue(Taxable::DIGITAL_PRODUCT)) {
            $taxable->getResult()->lockResult();
        }
    }
}
