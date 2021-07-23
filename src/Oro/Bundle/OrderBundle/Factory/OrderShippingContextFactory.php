<?php

namespace Oro\Bundle\OrderBundle\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class OrderShippingContextFactory implements ShippingContextFactoryInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var OrderShippingLineItemConverterInterface
     */
    private $shippingLineItemConverter;

    /**
     * @var ShippingContextBuilderFactoryInterface|null
     */
    private $shippingContextBuilderFactory;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter,
        ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory = null
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
    }

    /**
     * @param Order $order
     * @return ShippingContextInterface
     */
    public function create($order)
    {
        $this->ensureApplicable($order);

        if (null === $this->shippingContextBuilderFactory) {
            return null;
        }

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $order,
            (string)$order->getId()
        );

        $subtotal = Price::create(
            $order->getSubtotal(),
            $order->getCurrency()
        );

        $shippingContextBuilder
            ->setSubTotal($subtotal)
            ->setCurrency($order->getCurrency())
            ->setPaymentMethod($this->getOrderPaymentMethodIdentifier($order));

        if (null !== $order->getWebsite()) {
            $shippingContextBuilder->setWebsite($order->getWebsite());
        }

        $convertedLineItems = $this->shippingLineItemConverter->convertLineItems($order->getLineItems());

        if (null !== $order->getShippingAddress()) {
            $shippingContextBuilder->setShippingAddress($order->getShippingAddress());
        }

        if (null !== $order->getBillingAddress()) {
            $shippingContextBuilder->setBillingAddress($order->getBillingAddress());
        }

        if (null !== $order->getCustomer()) {
            $shippingContextBuilder->setCustomer($order->getCustomer());
        }

        if (null !== $order->getCustomerUser()) {
            $shippingContextBuilder->setCustomerUser($order->getCustomerUser());
        }

        if (null !== $convertedLineItems) {
            $shippingContextBuilder->setLineItems($convertedLineItems);
        }

        return $shippingContextBuilder->getResult();
    }

    /**
     * @param object $entity
     * @throws \InvalidArgumentException
     */
    protected function ensureApplicable($entity)
    {
        if (!is_a($entity, Order::class)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                Order::class,
                is_object($entity) ? get_class($entity) : gettype($entity)
            ));
        }
    }

    /**
     * @param Order $order
     *
     * @return null|string
     */
    private function getOrderPaymentMethodIdentifier(Order $order)
    {
        $paymentTransaction = $this->findFirstPaymentTransaction($order);

        if (null !== $paymentTransaction) {
            return $paymentTransaction->getPaymentMethod();
        }

        return null;
    }

    /**
     * @param Order $order
     *
     * @return null|object|PaymentTransaction
     */
    private function findFirstPaymentTransaction(Order $order)
    {
        $repository = $this->doctrineHelper->getEntityRepository(PaymentTransaction::class);

        return $repository->findOneBy([
            'entityClass' => Order::class,
            'entityIdentifier' => $order->getId()
        ]);
    }
}
