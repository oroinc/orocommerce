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
 * Gets parameters needed to create PaymentContext from Checkout and other sources
 */
class CheckoutPaymentContextFactory
{
    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var SubtotalProviderInterface
     */
    protected $checkoutSubtotalProvider;

    /**
     * @var TotalProcessorProvider
     */
    protected $totalProcessor;

    /**
     * @var OrderPaymentLineItemConverterInterface
     */
    private $paymentLineItemConverter;

    /**
     * @var PaymentContextBuilderFactoryInterface|null
     */
    private $paymentContextBuilderFactory;

    /**
     * @var ShippingOriginProvider
     */
    private $shippingOriginProvider;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param SubtotalProviderInterface $checkoutSubtotalProvider
     * @param TotalProcessorProvider $totalProcessor
     * @param OrderPaymentLineItemConverterInterface $paymentLineItemConverter
     * @param ShippingOriginProvider $shippingOriginProvider
     * @param null|PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory
     */
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

    /**
     * @param Checkout $checkout
     *
     * @return PaymentContextInterface|null
     */
    public function create(Checkout $checkout)
    {
        if (null === $this->paymentContextBuilderFactory) {
            return null;
        }

        $lineItems = $this->checkoutLineItemsManager->getData($checkout);
        $convertedLineItems = $this->paymentLineItemConverter->convertLineItems($lineItems);

        $paymentContextBuilder = $this->paymentContextBuilderFactory->createPaymentContextBuilder(
            $checkout,
            (string)$checkout->getId()
        );

        $subtotal = $this->checkoutSubtotalProvider->getSubtotal($checkout);
        $subtotalPrice = Price::create(
            $subtotal->getAmount(),
            $subtotal->getCurrency()
        );

        $paymentContextBuilder
            ->setSubTotal($subtotalPrice)
            ->setCurrency($checkout->getCurrency());

        if (null !== $checkout->getWebsite()) {
            $paymentContextBuilder
                ->setWebsite($checkout->getWebsite());
        }

        $this->addAddresses($paymentContextBuilder, $checkout);

        if (null !== $checkout->getShippingMethod()) {
            $paymentContextBuilder->setShippingMethod($checkout->getShippingMethod());
        }

        if (null !== $convertedLineItems && !$convertedLineItems->isEmpty()) {
            $paymentContextBuilder->setLineItems($convertedLineItems);
        }

        if (null !== $checkout->getCustomer()) {
            $paymentContextBuilder->setCustomer($checkout->getCustomer());
            $paymentContextBuilder->setCustomerUser($checkout->getCustomerUser());
        }

        return $paymentContextBuilder->getResult();
    }

    /**
     * @param PaymentContextBuilderInterface $paymentContextBuilder
     * @param Checkout $checkout
     */
    private function addAddresses(
        PaymentContextBuilderInterface $paymentContextBuilder,
        Checkout $checkout
    ) {
        if (null !== $checkout->getBillingAddress()) {
            $paymentContextBuilder->setBillingAddress($checkout->getBillingAddress());
        }

        if (null !== $checkout->getShippingAddress()) {
            $paymentContextBuilder->setShippingAddress($checkout->getShippingAddress());
        }

        $shippingOrigin = $this->shippingOriginProvider->getSystemShippingOrigin();
        if (null !== $shippingOrigin) {
            $paymentContextBuilder->setShippingOrigin($shippingOrigin);
        }
    }
}
