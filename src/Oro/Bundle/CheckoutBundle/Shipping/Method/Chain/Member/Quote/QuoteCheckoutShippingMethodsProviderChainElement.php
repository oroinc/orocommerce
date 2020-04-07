<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Quote;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\AbstractCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Shipping\Configuration\QuoteShippingConfigurationFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

/**
 * Provides applicable shipping methods views and shipping prices for checkout created from Quote.
 */
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
     * @var CheckoutShippingContextProvider|null
     */
    private $checkoutShippingContextProvider;

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
     * @param CheckoutShippingContextProvider|null $checkoutShippingContextProvider
     */
    public function setCheckoutShippingContextProvider(
        ?CheckoutShippingContextProvider $checkoutShippingContextProvider
    ): void {
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
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
        $context = $this->getShippingContext($checkout);

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

        return $this->shippingConfiguredPriceProvider->getPrice(
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType(),
            $configuration,
            $this->getShippingContext($checkout)
        );
    }

    /**
     * @param Checkout $checkout
     *
     * @return ShippingContextInterface
     */
    private function getShippingContext(Checkout $checkout): ShippingContextInterface
    {
        if ($this->checkoutShippingContextProvider) {
            return $this->checkoutShippingContextProvider->getContext($checkout);
        }

        return $this->checkoutShippingContextFactory->create($checkout);
    }
}
