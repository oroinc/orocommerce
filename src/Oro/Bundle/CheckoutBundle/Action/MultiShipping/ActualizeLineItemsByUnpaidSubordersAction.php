<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Update checkout line items, leave only products that were in sub-orders with unsuccessful payments.
 */
class ActualizeLineItemsByUnpaidSubordersAction extends AbstractAction
{
    private PaymentStatusManager $paymentStatusManager;
    private CheckoutLineItemsProvider $checkoutLineItemsProvider;
    private PropertyPath $order;
    private PropertyPath $checkout;

    public function __construct(
        ContextAccessor $contextAccessor,
        PaymentStatusManager $paymentStatusManager,
        CheckoutLineItemsProvider $checkoutLineItemsProvider,
    ) {
        parent::__construct($contextAccessor);
        $this->paymentStatusManager = $paymentStatusManager;
        $this->checkoutLineItemsProvider = $checkoutLineItemsProvider;
    }

    #[\Override]
    protected function executeAction($context)
    {
        /** @var Checkout $checkout */
        $checkout = $this->contextAccessor->getValue($context, $this->checkout);
        /** @var Order $order */
        $order = $this->contextAccessor->getValue($context, $this->order);

        $notPaidLineItems = [];
        foreach ($order->getSubOrders() as $subOrder) {
            if ($this->isProcessed($subOrder)) {
                continue;
            }

            $notPaidLineItems[] = $subOrder->getLineItems()->toArray();
        }

        if ($notPaidLineItems) {
            $notPaidLineItemsCollection = new ArrayCollection(array_merge(...$notPaidLineItems));
            $paidSkus = $this->checkoutLineItemsProvider->getProductSkusWithDifferences(
                $notPaidLineItemsCollection,
                $checkout->getLineItems()
            );
            $checkout->setLineItems(
                $checkout->getLineItems()->filter(static function (CheckoutLineItem $lineItem) use ($paidSkus) {
                    return !\in_array($lineItem->getProductSku(), $paidSkus, true);
                })
            );
        } else {
            $checkout->getLineItems()->clear();
        }
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (!\array_key_exists('order', $options)) {
            throw new InvalidParameterException('"order" parameter is required');
        }
        $this->order = $options['order'];

        if (!\array_key_exists('checkout', $options)) {
            throw new InvalidParameterException('"checkout" parameter is required');
        }
        $this->checkout = $options['checkout'];

        return $this;
    }

    private function isProcessed(Order $order): bool
    {
        return \in_array(
            (string) $this->paymentStatusManager->getPaymentStatus($order),
            [
                PaymentStatuses::AUTHORIZED,
                PaymentStatuses::PAID_IN_FULL
            ],
            true
        );
    }
}
