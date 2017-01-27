<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Quote;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\AbstractCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Shipping\Configuration\QuoteShippingConfigurationFactory;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

class QuoteCheckoutShippingMethodsProviderChainElement extends AbstractCheckoutShippingMethodsProviderChainElement
{
    /**
     * @var CheckoutShippingContextFactory
     */
    private $checkoutShippingContextFactory;

    /**
     * @var ShippingConfiguredPriceProviderInterface
     */
    private $shippingConfiguredPriceProvider;

    /**
     * @var QuoteShippingConfigurationFactory
     */
    private $quoteShippingConfigurationFactory;

    /**
     * @param CheckoutShippingContextFactory $checkoutShippingContextFactory
     * @param ShippingConfiguredPriceProviderInterface $shippingConfiguredPriceProvider
     * @param QuoteShippingConfigurationFactory $quoteShippingConfigurationFactory
     */
    public function __construct(
        CheckoutShippingContextFactory $checkoutShippingContextFactory,
        ShippingConfiguredPriceProviderInterface $shippingConfiguredPriceProvider,
        QuoteShippingConfigurationFactory $quoteShippingConfigurationFactory
    ) {
        $this->checkoutShippingContextFactory = $checkoutShippingContextFactory;
        $this->shippingConfiguredPriceProvider = $shippingConfiguredPriceProvider;
        $this->quoteShippingConfigurationFactory = $quoteShippingConfigurationFactory;
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
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(Checkout $checkout)
    {
        $successorViews = parent::getApplicableMethodsViews($checkout);

        if (false === $successorViews->isEmpty()) {
            return $successorViews;
        }

        $quote = $this->extractQuoteFromCheckout($checkout);

        if (null === $quote) {
            return $successorViews;
        }

        $configuration = $this->quoteShippingConfigurationFactory->createQuoteShippingConfig($quote);
        $context = $this->checkoutShippingContextFactory->create($checkout);

        return $this->shippingConfiguredPriceProvider->getApplicableMethodsViews($configuration, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(Checkout $checkout)
    {
        $successorPrice = parent::getPrice($checkout);

        if (null !== $successorPrice) {
            return $successorPrice;
        }

        $quote = $this->extractQuoteFromCheckout($checkout);

        if (null === $quote) {
            return $successorPrice;
        }

        $configuration = $this->quoteShippingConfigurationFactory->createQuoteShippingConfig($quote);
        $context = $this->checkoutShippingContextFactory->create($checkout);

        return $this->shippingConfiguredPriceProvider->getPrice(
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType(),
            $configuration,
            $context
        );
    }
}
