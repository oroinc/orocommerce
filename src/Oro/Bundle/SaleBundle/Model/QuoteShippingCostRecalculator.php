<?php

namespace Oro\Bundle\SaleBundle\Model;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Factory\QuoteShippingContextFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class QuoteShippingCostRecalculator
{
    /**
     * @var QuoteShippingContextFactory
     */
    private $quoteShippingContextFactory;

    /**
     * @var ShippingPriceProvider
     */
    private $shippingPriceProvider;

    /**
     * QuoteShippingCostRecalculator constructor.
     *
     * @param QuoteShippingContextFactory $quoteShippingContextFactory
     */
    public function __construct(QuoteShippingContextFactory $quoteShippingContextFactory)
    {
        $this->quoteShippingContextFactory = $quoteShippingContextFactory;
    }

    /**
     * @param ShippingPriceProvider $shippingPriceProvider
     */
    public function setShippingPriceProvider(ShippingPriceProvider $shippingPriceProvider)
    {
        $this->shippingPriceProvider = $shippingPriceProvider;
    }

    /**
     * @param Quote $quote
     */
    public function recalculateQuoteShippingCost(Quote $quote)
    {
        if (null === $this->shippingPriceProvider) {
            return;
        }

        if (null !== $quote->getOverriddenShippingCostAmount()) {
            return;
        }

        $shippingContext = $this->quoteShippingContextFactory->create($quote);

        $price = $this->shippingPriceProvider->getPrice(
            $shippingContext,
            $quote->getShippingMethod(),
            $quote->getShippingMethodType()
        );

        $quote->setEstimatedShippingCostAmount($price->getValue());
    }
}
