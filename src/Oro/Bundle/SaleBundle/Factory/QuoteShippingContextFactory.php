<?php

namespace Oro\Bundle\SaleBundle\Factory;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;

class QuoteShippingContextFactory
{
    /** @var ShippingContextFactory|null $shippingContextFactory */
    private $shippingContextFactory;

    /**
     * @param ShippingContextFactory $shippingContextFactory
     */
    public function setShippingContextFactory(ShippingContextFactory $shippingContextFactory)
    {
        $this->shippingContextFactory = $shippingContextFactory;
    }

    /**
     * @param Quote $quote
     *
     * @return null|ShippingContext
     */
    public function create(Quote $quote)
    {
        if (!$this->shippingContextFactory) {
            return null;
        }

        $shippingContext = $this->shippingContextFactory->create();

        $shippingContext->setShippingAddress($quote->getShippingAddress());
        $shippingContext->setCurrency($quote->getCurrency());
        $shippingContext->setSourceEntity($quote);
        $shippingContext->setSourceEntityIdentifier($quote->getId());
        
        if ($quote->getQuoteProducts()) {
            $shippingContext->setLineItems($quote->getQuoteProducts()->toArray());
        }
        
        return $shippingContext;
    }
}
