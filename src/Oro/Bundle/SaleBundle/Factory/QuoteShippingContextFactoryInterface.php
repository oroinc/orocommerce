<?php

namespace Oro\Bundle\SaleBundle\Factory;

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
