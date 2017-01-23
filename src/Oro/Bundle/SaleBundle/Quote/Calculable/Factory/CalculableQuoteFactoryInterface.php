<?php

namespace Oro\Bundle\SaleBundle\Quote\Calculable\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Quote\Calculable\CalculableQuoteInterface;

interface CalculableQuoteFactoryInterface
{
    /**
     * @param ArrayCollection $shippingLineItems
     *
     * @return CalculableQuoteInterface
     */
    public function createCalculableQuote(ArrayCollection $shippingLineItems);
}
