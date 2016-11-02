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

            if ($order->getShippingAddress()) {
                $shippingContext->setShippingAddress($order->getShippingAddress());
            }
            if ($order->getBillingAddress()) {
                $shippingContext->setBillingAddress($order->getBillingAddress());
            }
            if ($order->getCurrency()) {
                $shippingContext->setCurrency($order->getCurrency());
            }
            if ($order->getLineItems()) {
                $shippingContext->setLineItems($order->getLineItems()->toArray());
            }

            /** @var PaymentTransactionRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository(PaymentTransaction::class);
            /** @var PaymentTransaction $paymentTransaction */
            $paymentTransaction = $repository->findOneBy([
                'entityClass' => Order::class,
                'entityIdentifier' => $order->getId()
            ]);
            if ($paymentTransaction instanceof PaymentTransaction) {
                $shippingContext->setPaymentMethod($paymentTransaction->getPaymentMethod());
            }
            if ($order->getSubtotal() && $order->getCurrency()) {
                $shippingContext->setSubtotal(Price::create($order->getSubtotal(), $order->getCurrency()));
            }

            return $shippingContext;
        }
        return null;
    }
}
