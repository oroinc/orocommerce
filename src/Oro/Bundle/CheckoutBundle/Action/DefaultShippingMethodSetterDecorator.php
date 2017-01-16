<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

class DefaultShippingMethodSetterDecorator
{
    /**
     * @var DefaultShippingMethodSetter
     */
    private $defaultShippingMethodSetter;

    /**
     * @param DefaultShippingMethodSetter $defaultShippingMethodSetter
     */
    public function __construct(DefaultShippingMethodSetter $defaultShippingMethodSetter)
    {
        $this->defaultShippingMethodSetter = $defaultShippingMethodSetter;
    }

    /**
     * @param Checkout $checkout
     *
     * @return null|Quote
     */
    private function extractQuoteFromCheckout(Checkout $checkout)
    {
        $sourceEntity = $checkout->getSourceEntity();

        return $sourceEntity instanceof QuoteDemand ? $sourceEntity->getQuote() : null;
    }

    /**
     * @param Checkout $checkout
     */
    public function setDefaultShippingMethod(Checkout $checkout)
    {
        if ($checkout->getShippingMethod()) {
            return;
        }

        $quote = $this->extractQuoteFromCheckout($checkout);

        if ($quote) {
            $checkout->setShippingMethod($quote->getShippingMethod());
        } else {
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }
    }
}
