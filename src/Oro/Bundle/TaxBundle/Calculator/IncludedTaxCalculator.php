<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * (inclTax * taxRate) / (1 + taxRate)
 */
class IncludedTaxCalculator implements TaxCalculatorInterface
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
            $inclTax = BigDecimal::of($amount);
            $taxRate = BigDecimal::of($taxRate)->abs();

            $taxAmount = $inclTax
                ->multipliedBy($taxRate)
                ->dividedBy($taxRate->plus(1), TaxationSettingsProvider::CALCULATION_SCALE, RoundingMode::HALF_UP);

            $exclTax = $inclTax->minus($taxAmount);

            $this->cache[$key] = ResultElement::create($inclTax, $exclTax, $taxAmount);
        }

        return clone $this->cache[$key];
    }

    /** {@inheritdoc} */
    public function getAmountKey()
    {
        return ResultElement::INCLUDING_TAX;
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
