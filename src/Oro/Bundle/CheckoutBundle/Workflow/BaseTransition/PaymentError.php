<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

/**
 * Base implementation of checkout payment_error transition.
 */
class PaymentError extends TransitionServiceAbstract
{
    private ?PaymentStatusProviderInterface $paymentStatusProvider = null;

    public function __construct(
        private ManagerRegistry $registry,
        private TransitionServiceInterface $baseTrnasition
    ) {
    }

    public function setPaymentProviderManager(PaymentStatusProviderInterface $paymentStatusProvider): void
    {
        $this->paymentStatusProvider = $paymentStatusProvider;
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        $this->baseTrnasition->execute($workflowItem);

        $data = $workflowItem->getData();
        $order = $data->offsetGet('order');
        if (null !== $order && !$this->isOrderPaid($order)) {
            $this->registry->getManagerForClass(Order::class)?->remove($order);
            $data->offsetUnset('order');
        }
    }

    private function isOrderPaid(Order $order): bool
    {
        $paymentStatus = $this->paymentStatusProvider->getPaymentStatus($order);

        return in_array($paymentStatus, [
            PaymentStatusProvider::FULL,
            PaymentStatusProvider::PARTIALLY,
            PaymentStatusProvider::AUTHORIZED,
            PaymentStatusProvider::AUTHORIZED_PARTIALLY,
        ], true);
    }
}
