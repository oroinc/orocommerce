<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingOriginProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Creates a shipping context for a specific checkout object.
 */
class CheckoutShippingContextFactory
{
    private CheckoutLineItemsManager $checkoutLineItemsManager;
    private SubtotalProviderInterface $checkoutSubtotalProvider;
    private OrderShippingLineItemConverterInterface $shippingLineItemConverter;
    private CheckoutShippingOriginProviderInterface $shippingOriginProvider;
    private ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory;

    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        SubtotalProviderInterface $checkoutSubtotalProvider,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter,
        CheckoutShippingOriginProviderInterface $shippingOriginProvider,
        ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->checkoutSubtotalProvider = $checkoutSubtotalProvider;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->shippingOriginProvider = $shippingOriginProvider;
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
    }

    public function create(Checkout $checkout): ShippingContextInterface
    {
        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $checkout,
            (string)$checkout->getId()
        );

        $this->addAddresses($shippingContextBuilder, $checkout);
        $this->addCustomer($shippingContextBuilder, $checkout);
        $this->addSubTotal($shippingContextBuilder, $checkout);

        if (null !== $checkout->getPaymentMethod()) {
            $shippingContextBuilder->setPaymentMethod($checkout->getPaymentMethod());
        }

        $shippingContextBuilder->setLineItems(
            $this->shippingLineItemConverter->convertLineItems($this->checkoutLineItemsManager->getData($checkout))
        );

        return $shippingContextBuilder->getResult();
    }

    private function addAddresses(
        ShippingContextBuilderInterface $shippingContextBuilder,
        Checkout $checkout
    ): void {
        if (null !== $checkout->getBillingAddress()) {
            $shippingContextBuilder->setBillingAddress($checkout->getBillingAddress());
        }

        if (null !== $checkout->getShippingAddress()) {
            $shippingContextBuilder->setShippingAddress($checkout->getShippingAddress());
        }

        $shippingContextBuilder->setShippingOrigin($this->shippingOriginProvider->getShippingOrigin($checkout));
    }

    private function addCustomer(
        ShippingContextBuilderInterface $shippingContextBuilder,
        Checkout $checkout
    ): void {
        if (null !== $checkout->getCustomer()) {
            $shippingContextBuilder->setCustomer($checkout->getCustomer());
        }

        if (null !== $checkout->getCustomerUser()) {
            $shippingContextBuilder->setCustomerUser($checkout->getCustomerUser());
        }

        if (null !== $checkout->getWebsite()) {
            $shippingContextBuilder->setWebsite($checkout->getWebsite());
        }
    }

    private function addSubTotal(
        ShippingContextBuilderInterface $shippingContextBuilder,
        Checkout $checkout
    ): void {
        $shippingContextBuilder->setCurrency($checkout->getCurrency());
        $subtotal = $this->checkoutSubtotalProvider->getSubtotal($checkout);
        $shippingContextBuilder->setSubTotal(Price::create($subtotal->getAmount(), $subtotal->getCurrency()));
    }
}
