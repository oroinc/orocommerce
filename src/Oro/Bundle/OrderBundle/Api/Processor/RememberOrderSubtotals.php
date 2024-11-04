<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Api\Repository\OrderSubtotalRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Stores order subtotals in shared data.
 */
class RememberOrderSubtotals implements ProcessorInterface
{
    public function __construct(
        private OrderSubtotalRepository $orderSubtotalRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var Order $order */
        $order = $context->getData();
        ComputeOrderSubtotals::addOrderSubtotal(
            $context,
            $order->getId(),
            $this->orderSubtotalRepository->getOrderSubtotals($order)
        );
    }
}
