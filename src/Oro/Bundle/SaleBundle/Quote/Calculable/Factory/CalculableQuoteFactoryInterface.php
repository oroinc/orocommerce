<?php

namespace Oro\Bundle\SaleBundle\Quote\Calculable\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Quote\Calculable\CalculableQuoteInterface;

/**
 * Defines the contract for creating calculable quote instances.
 *
 * Implementations create {@see CalculableQuoteInterface} instances from line item collections,
 * enabling the calculation of quote totals and subtotals.
 */
interface CalculableQuoteFactoryInterface
{
    /**
     * @param ArrayCollection $lineItems
     *
     * @return CalculableQuoteInterface
     */
    public function createCalculableQuote(ArrayCollection $lineItems);
}
