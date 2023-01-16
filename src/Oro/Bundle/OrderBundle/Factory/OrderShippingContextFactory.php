<?php

namespace Oro\Bundle\OrderBundle\Factory;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Creates a shipping context based on an order entity.
 */
class OrderShippingContextFactory implements ShippingContextFactoryInterface
{
    private ManagerRegistry $doctrine;
    private OrderShippingLineItemConverterInterface $shippingLineItemConverter;
    private ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory;

    public function __construct(
        ManagerRegistry $doctrine,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter,
        ShippingContextBuilderFactoryInterface $shippingContextBuilderFactory
    ) {
        $this->doctrine = $doctrine;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->shippingContextBuilderFactory = $shippingContextBuilderFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create(object $entity): ShippingContextInterface
    {
        if (!$entity instanceof Order) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                Order::class,
                get_debug_type($entity)
            ));
        }

        $shippingContextBuilder = $this->shippingContextBuilderFactory->createShippingContextBuilder(
            $entity,
            (string)$entity->getId()
        );

        $subtotal = Price::create($entity->getSubtotal(), $entity->getCurrency());

        $shippingContextBuilder
            ->setSubTotal($subtotal)
            ->setCurrency($entity->getCurrency())
            ->setPaymentMethod($this->findFirstPaymentTransaction($entity)?->getPaymentMethod());

        if (null !== $entity->getWebsite()) {
            $shippingContextBuilder->setWebsite($entity->getWebsite());
        }

        if (null !== $entity->getShippingAddress()) {
            $shippingContextBuilder->setShippingAddress($entity->getShippingAddress());
        }

        if (null !== $entity->getBillingAddress()) {
            $shippingContextBuilder->setBillingAddress($entity->getBillingAddress());
        }

        if (null !== $entity->getCustomer()) {
            $shippingContextBuilder->setCustomer($entity->getCustomer());
        }

        if (null !== $entity->getCustomerUser()) {
            $shippingContextBuilder->setCustomerUser($entity->getCustomerUser());
        }

        $shippingContextBuilder->setLineItems(
            $this->shippingLineItemConverter->convertLineItems($entity->getLineItems())
        );

        return $shippingContextBuilder->getResult();
    }

    private function findFirstPaymentTransaction(Order $order): ?PaymentTransaction
    {
        return $this->doctrine->getRepository(PaymentTransaction::class)
            ->findOneBy(['entityClass' => Order::class, 'entityIdentifier' => $order->getId()]);
    }
}
