<?php

namespace Oro\Bundle\OrderBundle\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;

class ShippingContextProviderFactory
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @var ShippingContextFactory|null
     */
    protected $shippingContextFactory;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ShippingContextFactory|null $shippingContextFactory
     */
    public function __construct(DoctrineHelper $doctrineHelper, ShippingContextFactory $shippingContextFactory = null)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->shippingContextFactory = $shippingContextFactory;
    }

    /**
     * @param Order $order
     * @return ShippingContext
     */
    public function create(Order $order)
    {
        if ($this->shippingContextFactory) {
            $shippingContext = $this->shippingContextFactory->create();

            $shippingContext->setShippingAddress($order->getShippingAddress());
            $shippingContext->setBillingAddress($order->getBillingAddress());
            $shippingContext->setCurrency($order->getCurrency());
            $shippingContext->setLineItems($order->getLineItems()->toArray());

            /** @var PaymentTransactionRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository(PaymentTransaction::class);
            /** @var PaymentTransaction $paymentTransaction */
            $paymentTransaction = $repository->findOneBy([
                'entityClass' => Order::class,
                'entityIdentifier' => $order->getId()
            ]);
            $shippingContext->setPaymentMethod($paymentTransaction->getPaymentMethod());

            $subtotal = Price::create(
                $order->getSubtotal(),
                $order->getCurrency()
            );

            $shippingContext->setSubtotal($subtotal);

            return $shippingContext;
        }
        return null;
    }
}
