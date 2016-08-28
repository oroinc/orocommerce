<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;

class ShippingContextProviderFactory
{
    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var TotalProcessorProvider
     */
    protected $totalProcessor;

    /**
     * @var ShippingContextFactory
     */
    protected $shippingContextFactory;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param TotalProcessorProvider $totalProcessor
     * @param ShippingContextFactory $shippingContextFactory
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        TotalProcessorProvider $totalProcessor,
        ShippingContextFactory $shippingContextFactory
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->totalProcessor = $totalProcessor;
        $this->shippingContextFactory = $shippingContextFactory;
    }

    /**
     * @param Checkout $checkout
     * @return ShippingContext
     */
    public function create(Checkout $checkout)
    {
        $shippingContext = $this->shippingContextFactory->create();

        $shippingContext->setShippingAddress($checkout->getShippingAddress());
        $shippingContext->setBillingAddress($checkout->getBillingAddress());
        $shippingContext->setCurrency($checkout->getCurrency());
        $shippingContext->setPaymentMethod($checkout->getPaymentMethod());
        $shippingContext->setLineItems(
            $this->checkoutLineItemsManager->getData($checkout)
        );

        $total = $this->totalProcessor->getTotal($checkout);
        $subtotal = Price::create(
            $total->getAmount(),
            $total->getCurrency()
        );

        $shippingContext->setSubtotal($subtotal);

        return $shippingContext;
    }
}
