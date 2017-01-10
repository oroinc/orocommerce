<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface QuoteShippingContextFactoryInterface
{
    /**
     * @param Quote $quote
     *
     * @return ShippingContextInterface
     */
    public function create(Quote $quote);
}
