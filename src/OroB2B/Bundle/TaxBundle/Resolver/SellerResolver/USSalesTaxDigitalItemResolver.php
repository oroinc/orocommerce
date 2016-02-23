<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Matcher\UnitedStatesHelper;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
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

        if ($taxable->getResult()->isResultLocked()) {
            return;
        }

        $isStateWithoutDigitalTax = UnitedStatesHelper::isStateWithoutDigitalTax(
            $address->getCountryIso2(),
            $address->getRegionCode()
        );

        if ($isStateWithoutDigitalTax && $taxable->getContextValue(Taxable::DIGITAL_PRODUCT)) {
            $unitPrice = BigDecimal::of($taxable->getPrice());
            $unitResultElement = ResultElement::create($unitPrice, $unitPrice, BigDecimal::zero(), BigDecimal::zero());
            $taxable->getResult()->offsetSet(Result::UNIT, $unitResultElement);

            $rowPrice = $unitPrice->multipliedBy($taxable->getQuantity());
            $rowResultElement = ResultElement::create($rowPrice, $rowPrice, BigDecimal::zero(), BigDecimal::zero());
            $taxable->getResult()->offsetSet(Result::ROW, $rowResultElement);

            $taxable->getResult()->lockResult();
        }
    }
}
