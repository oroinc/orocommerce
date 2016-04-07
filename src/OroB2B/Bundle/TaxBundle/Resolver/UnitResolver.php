<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\Result;

class UnitResolver extends AbstractUnitRowResolver
{
    /**
     * @var TaxCalculatorInterface
     */
    protected $calculator;

    /**
     * @param TaxCalculatorInterface $calculator
     */
    public function __construct(TaxCalculatorInterface $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param BigDecimal $taxableAmount
     */
    public function resolveUnitPrice(Result $result, array $taxRules, BigDecimal $taxableAmount)
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
