<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Oro\Bundle\TaxBundle\Model\ResultElement;

/**
 * Abstract class for a tax calculator provides cache layer to optimize performance.
 */
abstract class AbstractTaxCalculator implements TaxCalculatorInterface
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
            $this->cache[$key] = $this->doCalculate($amount, $taxRate);
        }

        return clone $this->cache[$key];
    }

    /**
     * @param string $amount
     * @param string $taxRate
     * @return ResultElement
     */
    abstract protected function doCalculate(string $amount, string $taxRate): ResultElement;

    /**
     * @param string $amount
     * @param string $taxRate
     * @return string
     */
    private function getCacheKey(string $amount, string $taxRate): string
    {
        return sprintf('%s|%s', $amount, $taxRate);
    }
}
