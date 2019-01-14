<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
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
     * @var SubtotalProviderInterface
     */
    protected $checkoutSubtotalProvider;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param TotalProcessorProvider $totalProcessor
     * @param OrderPaymentLineItemConverterInterface $paymentLineItemConverter
     * @param null|PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        TotalProcessorProvider $totalProcessor,
        OrderPaymentLineItemConverterInterface $paymentLineItemConverter,
        PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory = null
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->totalProcessor = $totalProcessor;
        $this->paymentLineItemConverter = $paymentLineItemConverter;
        $this->paymentContextBuilderFactory = $paymentContextBuilderFactory;
    }

    /**
     * @param ShippingOriginProvider $shippingOriginProvider
     */
    public function setShippingOriginProvider(ShippingOriginProvider $shippingOriginProvider)
    {
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * @param SubtotalProviderInterface $checkoutSubtotalProvider
     */
    public function setCheckoutSubtotalProvider(SubtotalProviderInterface $checkoutSubtotalProvider)
    {
        $this->checkoutSubtotalProvider = $checkoutSubtotalProvider;
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
