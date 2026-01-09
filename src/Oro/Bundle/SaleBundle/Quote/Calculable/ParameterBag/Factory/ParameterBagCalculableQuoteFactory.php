<?php

namespace Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Quote\Calculable\Factory\CalculableQuoteFactoryInterface;
use Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag\ParameterBagCalculableQuote;

/**
 * Creates calculable quote instances using ParameterBag-based implementation.
 *
 * Implements the {@see CalculableQuoteFactoryInterface} to create {@see ParameterBagCalculableQuote} instances
 * from line item collections, providing a flexible parameter bag-based approach to quote calculation.
 */
class ParameterBagCalculableQuoteFactory implements CalculableQuoteFactoryInterface
{
    #[\Override]
    public function createCalculableQuote(ArrayCollection $lineItems)
    {
        return new ParameterBagCalculableQuote(
            [
                ParameterBagCalculableQuote::FIELD_LINE_ITEMS => $lineItems
            ]
        );
    }
}
