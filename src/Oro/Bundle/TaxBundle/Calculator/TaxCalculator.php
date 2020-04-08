<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Component\Math\BigDecimal;

/**
 * ($exclTax * taxRate) + $exclTax
 */
class TaxCalculator implements TaxCalculatorInterface
{
    /** @var array */
    private $cache = [];

    /**
     * {@inheritdoc}
     */
    public function calculate($amount, $taxRate)
    {
        $key = $this->getCacheKey($amount, $taxRate);
        if (!isset($this->cache[$key])) {
            $exclTax = BigDecimal::of($amount);
            $taxRate = BigDecimal::of($taxRate)->abs();

            $taxAmount = $exclTax->multipliedBy($taxRate);
            $inclTax = $exclTax->plus($taxAmount);

            $this->cache[$key] = ResultElement::create($inclTax, $exclTax, $taxAmount);
        }

        return clone $this->cache[$key];
    }

    /** {@inheritdoc} */
    public function getAmountKey()
    {
        return ResultElement::EXCLUDING_TAX;
    }

    /**
     * @param string $amount
     * @param string $taxRate
     *
     * @return string
     */
    private function getCacheKey(string $amount, string $taxRate): string
    {
        return sprintf('%s|%s', $amount, $taxRate);
    }
}
