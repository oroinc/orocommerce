<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\CustomerAddressItemResolver;

class USSalesTaxDigitalItemResolver extends CustomerAddressItemResolver
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

        if ($taxable->getContextValue(Taxable::DIGITAL_PRODUCT)) {
            return;
        }

        $taxRules = $this->matcher->match($address);
        $taxableUnitPrice = BigDecimal::of($taxable->getPrice());
        $taxableAmount = $taxableUnitPrice->multipliedBy($taxable->getQuantity());

        $result = $taxable->getResult();
        $this->resolveUnitPrice($result, $taxRules, $taxableUnitPrice);
        $this->resolveRowTotal($result, $taxRules, $taxableAmount);
    }
}
