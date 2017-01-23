<?php

namespace Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Quote\Calculable\Factory\CalculableQuoteFactoryInterface;
use Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag\ParameterBagCalculableQuote;

class ParameterBagCalculableQuoteFactory implements CalculableQuoteFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createCalculableQuote(ArrayCollection $lineItems)
    {
        return new ParameterBagCalculableQuote(
            [
                ParameterBagCalculableQuote::FIELD_LINE_ITEMS => $lineItems
            ]
        );
    }
}
