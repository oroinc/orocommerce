<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\USSalesTaxResolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;

/**
 * Provide basic methods for calculating digital US Sales tax.
 */
trait CalculateUSSalesTaxTrait
{
    protected function calculateUnitPriceAndRowTotal(Taxable $taxable): void
    {
        $result = $taxable->getResult();

        $unitPrice = BigDecimal::of($taxable->getPrice());
        $rowPrice = $unitPrice->multipliedBy($taxable->getQuantity());

        $unitResultElement = ResultElement::create($unitPrice, $unitPrice, BigDecimal::zero(), BigDecimal::zero());
        $rowResultElement = ResultElement::create($rowPrice, $rowPrice, BigDecimal::zero(), BigDecimal::zero());

        $result->offsetSet(Result::UNIT, $unitResultElement);
        $result->offsetSet(Result::ROW, $rowResultElement);

        $result->lockResult();
    }
}
