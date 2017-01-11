<?php

namespace Oro\Bundle\OrderBundle\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class OrderPaymentContextFactory
{
    /**
     * @var OrderPaymentLineItemConverterInterface
     */
    private $paymentLineItemConverter;

    /**
     * @var PaymentContextBuilderFactoryInterface|null
     */
    private $paymentContextBuilderFactory;

    /**
     * @param OrderPaymentLineItemConverterInterface $paymentLineItemConverter
     * @param null|PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory
     */
    public function __construct(
        OrderPaymentLineItemConverterInterface $paymentLineItemConverter,
        PaymentContextBuilderFactoryInterface $paymentContextBuilderFactory = null
    ) {
        $this->paymentLineItemConverter = $paymentLineItemConverter;
        $this->paymentContextBuilderFactory = $paymentContextBuilderFactory;
    }

    /**
     * @param Order $order
     * @return PaymentContextInterface
     */
    public function create(Order $order)
    {
        if (null === $this->paymentContextBuilderFactory) {
            return null;
        }

        $subtotal = Price::create(
            $order->getSubtotal(),
            $order->getCurrency()
        );

        $paymentContextBuilder = $this->paymentContextBuilderFactory->createPaymentContextBuilder(
            $order->getCurrency(),
            $subtotal,
            $order,
            (string)$order->getId()
        );

        $convertedLineItems = $this->paymentLineItemConverter->convertLineItems($order->getLineItems());

        if (null !== $order->getBillingAddress()) {
            $paymentContextBuilder->setBillingAddress($order->getBillingAddress());
        }

        if (null !== $order->getBillingAddress()) {
            $paymentContextBuilder->setBillingAddress($order->getBillingAddress());
        }

        if (null !== $order->getCustomer()) {
            $paymentContextBuilder->setCustomer($order->getCustomer());
        }

        if (null !== $order->getCustomerUser()) {
            $paymentContextBuilder->setCustomerUser($order->getCustomerUser());
        }

        if (null !== $convertedLineItems && !$convertedLineItems->isEmpty()) {
            $paymentContextBuilder->setLineItems($convertedLineItems);
        }

        if (null !== $order->getShippingMethod()) {
            $paymentContextBuilder->setShippingMethod($order->getShippingMethod());
        }

        return $paymentContextBuilder->getResult();
    }
}
