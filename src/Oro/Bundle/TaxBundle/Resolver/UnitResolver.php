<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\Result;

/**
 * Tax resolver that calculate tax value and provides unit result.
 */
class UnitResolver
{
    use TaxCalculateResolverTrait;

    public function __construct(
        private TaxCalculatorInterface $calculator
    ) {
    }

    /**
     * @param TaxRule[] $taxRules
     */
    public function resolveUnitPrice(Result $result, array $taxRules, BigDecimal $taxableAmount): void
    {
        $taxRate = BigDecimal::zero();

        foreach ($taxRules as $taxRule) {
            $taxRate = $taxRate->plus($taxRule->getTax()->getRate());
        }

        $resultElement = $this->calculator->calculate($taxableAmount, $taxRate);
        $this->calculateAdjustment($resultElement);

        $result->offsetSet(Result::UNIT, $resultElement);
    }
}
