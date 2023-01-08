<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Quote;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\AbstractCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Shipping\Configuration\QuoteShippingConfigurationFactory;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

/**
 * Provides views for all applicable shipping methods and calculate a shipping price
 * for a checkout created from a quote.
 */
class QuoteCheckoutShippingMethodsProviderChainElement extends AbstractCheckoutShippingMethodsProviderChainElement
{
    private CheckoutShippingContextProvider $checkoutShippingContextProvider;
    private ShippingConfiguredPriceProviderInterface $shippingConfiguredPriceProvider;
    private QuoteShippingConfigurationFactory $quoteShippingConfigurationFactory;

    public function __construct(
        CheckoutShippingContextProvider $checkoutShippingContextProvider,
        ShippingConfiguredPriceProviderInterface $shippingConfiguredPriceProvider,
        QuoteShippingConfigurationFactory $quoteShippingConfigurationFactory
    ) {
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
        $this->shippingConfiguredPriceProvider = $shippingConfiguredPriceProvider;
        $this->quoteShippingConfigurationFactory = $quoteShippingConfigurationFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getApplicableMethodsViews(Checkout $checkout): ShippingMethodViewCollection
    {
        $successorViews = parent::getApplicableMethodsViews($checkout);
        if (!$successorViews->isEmpty()) {
            return $successorViews;
        }

        $quote = $this->extractQuoteFromCheckout($checkout);
        if (null === $quote) {
            return $successorViews;
        }

        return $this->shippingConfiguredPriceProvider->getApplicableMethodsViews(
            $this->quoteShippingConfigurationFactory->createQuoteShippingConfig($quote),
            $this->checkoutShippingContextProvider->getContext($checkout)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(Checkout $checkout): ?Price
    {
        $successorPrice = parent::getPrice($checkout);
        if (null !== $successorPrice) {
            return $successorPrice;
        }

        $quote = $this->extractQuoteFromCheckout($checkout);
        if (null === $quote) {
            return null;
        }

        return $this->shippingConfiguredPriceProvider->getPrice(
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType(),
            $this->quoteShippingConfigurationFactory->createQuoteShippingConfig($quote),
            $this->checkoutShippingContextProvider->getContext($checkout)
        );
    }

    private function extractQuoteFromCheckout(Checkout $checkout): ?Quote
    {
        $sourceEntity = $checkout->getSourceEntity();

        return $sourceEntity instanceof QuoteDemand ? $sourceEntity->getQuote() : null;
    }
}
