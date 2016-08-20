<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver;

use Brick\Math\BigDecimal;

use Oro\Bundle\TaxBundle\Matcher\UnitedStatesHelper;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;

class DigitalItemResolver implements ResolverInterface
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

        $result = $taxable->getResult();
        if ($result->isResultLocked()) {
            return;
        }

        $isStateWithoutDigitalTax = UnitedStatesHelper::isStateWithoutDigitalTax(
            $address->getCountryIso2(),
            $address->getRegionCode()
        );

        if ($isStateWithoutDigitalTax && $taxable->getContextValue(Taxable::DIGITAL_PRODUCT)) {
            $unitPrice = BigDecimal::of($taxable->getPrice());
            $unitResultElement = ResultElement::create($unitPrice, $unitPrice, BigDecimal::zero(), BigDecimal::zero());
            $result->offsetSet(Result::UNIT, $unitResultElement);

            $rowPrice = $unitPrice->multipliedBy($taxable->getQuantity());
            $rowResultElement = ResultElement::create($rowPrice, $rowPrice, BigDecimal::zero(), BigDecimal::zero());
            $result->offsetSet(Result::ROW, $rowResultElement);

            $result->lockResult();

            $taxable->makeDestinationAddressTaxable();
        }
    }
}
