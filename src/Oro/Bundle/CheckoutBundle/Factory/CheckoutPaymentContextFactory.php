<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

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
    private $shippingLineItemConverter;

    /**
     * @var PaymentContextBuilderFactoryInterface|null
     */
    private $shippingContextBuilderFactory = null;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param TotalProcessorProvider $totalProcessor
     * @param OrderPaymentLineItemConverterInterface $shippingLineItemConverter
     * @param null|PaymentContextBuilderFactoryInterface $shippingContextBuilderFactory
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        TotalProcessorProvider $totalProcessor,
        OrderPaymentLineItemConverterInterface $shippingLineItemConverter,
        PaymentContextBuilderFactoryInterface $shippingContextBuilderFactory = null
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->totalProcessor = $totalProcessor;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
    }

    /**
     * @param Checkout $checkout
     *
     * @return PaymentContextInterface
     */
    public function create(Checkout $checkout)
    {
        if (null === $this->shippingContextBuilderFactory) {
            return null;
        }

        $lineItems = $this->checkoutLineItemsManager->getData($checkout);
        $convertedLineItems = $this->shippingLineItemConverter->convertLineItems($lineItems);

        $total = $this->totalProcessor->getTotal($checkout);
        $subtotal = Price::create(
            $total->getAmount(),
            $total->getCurrency()
        );

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createPaymentContextBuilder(
            $checkout->getCurrency(),
            $subtotal,
            $checkout,
            (string)$checkout->getId()
        );

        if (null !== $checkout->getBillingAddress()) {
            $shippingContextBuilder->setBillingAddress($checkout->getBillingAddress());
        }

        if (null !== $checkout->getBillingAddress()) {
            $shippingContextBuilder->setBillingAddress($checkout->getBillingAddress());
        }

        if (null !== $checkout->getShippingMethod()) {
            $shippingContextBuilder->setShippingMethod($checkout->getShippingMethod());
        }

        if (false === $convertedLineItems->isEmpty()) {
            $shippingContextBuilder->setLineItems($convertedLineItems);
        }

        if (null !== $checkout->getAccount()) {
            $shippingContextBuilder->setCustomer($checkout->getAccount());
            $shippingContextBuilder->setCustomerUser($checkout->getAccountUser());
        }

        return $shippingContextBuilder->getResult();
    }
}
