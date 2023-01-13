<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

/**
 * The factory to create a payment context for a specific checkout object.
 */
class CheckoutPaymentContextFactory
{
    private CheckoutLineItemsManager $checkoutLineItemsManager;
    private SubtotalProviderInterface $checkoutSubtotalProvider;
    private TotalProcessorProvider $totalProcessor;
    private OrderPaymentLineItemConverterInterface $paymentLineItemConverter;
    private ShippingOriginProvider $shippingOriginProvider;
    private ?PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory;

    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        SubtotalProviderInterface $checkoutSubtotalProvider,
        TotalProcessorProvider $totalProcessor,
        OrderPaymentLineItemConverterInterface $paymentLineItemConverter,
        ShippingOriginProvider $shippingOriginProvider,
        PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory = null
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->checkoutSubtotalProvider = $checkoutSubtotalProvider;
        $this->totalProcessor = $totalProcessor;
        $this->paymentLineItemConverter = $paymentLineItemConverter;
        $this->shippingOriginProvider = $shippingOriginProvider;
        $this->paymentContextBuilderFactory = $paymentContextBuilderFactory;
    }

    public function create(Checkout $checkout): ?PaymentContextInterface
    {
        if (null === $this->paymentContextBuilderFactory) {
            return null;
        }

        $paymentContextBuilder = $this->paymentContextBuilderFactory->createPaymentContextBuilder(
            $checkout,
            (string)$checkout->getId()
        );

        $this->addAddresses($paymentContextBuilder, $checkout);
        $this->addCustomer($paymentContextBuilder, $checkout);
        $this->addSubTotal($paymentContextBuilder, $checkout);

        if (null !== $checkout->getShippingMethod()) {
            $paymentContextBuilder->setShippingMethod($checkout->getShippingMethod());
        }

        $convertedLineItems = $this->paymentLineItemConverter->convertLineItems(
            $this->checkoutLineItemsManager->getData($checkout)
        );
        if (null !== $convertedLineItems && !$convertedLineItems->isEmpty()) {
            $paymentContextBuilder->setLineItems($convertedLineItems);
        }

        $paymentContextBuilder->setTotal($this->totalProcessor->getTotal($checkout)->getAmount());

        return $paymentContextBuilder->getResult();
    }

    private function addAddresses(
        PaymentContextBuilderInterface $paymentContextBuilder,
        Checkout $checkout
    ): void {
        if (null !== $checkout->getBillingAddress()) {
            $paymentContextBuilder->setBillingAddress($checkout->getBillingAddress());
        }

        if (null !== $checkout->getShippingAddress()) {
            $paymentContextBuilder->setShippingAddress($checkout->getShippingAddress());
        }

        $paymentContextBuilder->setShippingOrigin($this->shippingOriginProvider->getSystemShippingOrigin());
    }

    private function addCustomer(
        PaymentContextBuilderInterface $paymentContextBuilder,
        Checkout $checkout
    ): void {
        if (null !== $checkout->getCustomer()) {
            $paymentContextBuilder->setCustomer($checkout->getCustomer());
        }

        if (null !== $checkout->getCustomerUser()) {
            $paymentContextBuilder->setCustomerUser($checkout->getCustomerUser());
        }

        $website = $checkout->getWebsite();
        if (null !== $website) {
            $paymentContextBuilder->setWebsite($website);
        }
    }

    private function addSubTotal(
        PaymentContextBuilderInterface $paymentContextBuilder,
        Checkout $checkout
    ): void {
        $paymentContextBuilder->setCurrency($checkout->getCurrency());
        $subtotal = $this->checkoutSubtotalProvider->getSubtotal($checkout);
        $paymentContextBuilder->setSubTotal(Price::create($subtotal->getAmount(), $subtotal->getCurrency()));
    }
}
