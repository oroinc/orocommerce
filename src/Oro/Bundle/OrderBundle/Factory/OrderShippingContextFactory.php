<?php

namespace Oro\Bundle\OrderBundle\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class OrderShippingContextFactory
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var OrderShippingLineItemConverterInterface
     */
    private $shippingLineItemConverter = null;

    /**
     * @var ShippingContextBuilderFactoryInterface|null
     */
    private $shippingContextBuilderFactory = null;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param OrderShippingLineItemConverterInterface $shippingLineItemConverter
     * @param null|ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter,
        ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
    }

    /**
     * @param Order $order
     * @return ShippingContextInterface
     */
    public function create(Order $order)
    {
        if (null === $this->shippingContextBuilderFactory || null === $this->shippingLineItemConverter) {
            return null;
        }

        $subtotal = Price::create(
            $order->getSubtotal(),
            $order->getCurrency()
        );

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $order->getCurrency(),
            $subtotal,
            $order,
            (string)$order->getId()
        );

        if (null !== $order->getShippingAddress()) {
            $shippingContextBuilder->setShippingAddress($order->getShippingAddress());
        }

        if (null !== $order->getBillingAddress()) {
            $shippingContextBuilder->setBillingAddress($order->getBillingAddress());
        }

        if (!$order->getLineItems()->isEmpty()) {
            $shippingContextBuilder->setLineItems(
                $this->shippingLineItemConverter->convertLineItems($order->getLineItems())
            );
        }

        $repository = $this->doctrineHelper->getEntityRepository(PaymentTransaction::class);

        $paymentTransaction = $repository->findOneBy([
            'entityClass' => Order::class,
            'entityIdentifier' => $order->getId()
        ]);

        if (null !== $paymentTransaction) {
            /** @var PaymentTransaction $paymentTransaction */
            $shippingContextBuilder->setPaymentMethod($paymentTransaction->getPaymentMethod());
        }

        return $shippingContextBuilder->getResult();
    }
}
