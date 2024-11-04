<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Api\Repository\OrderSubtotalRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads order subtotals.
 */
class LoadOrderSubtotals implements ProcessorInterface
{
    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private OrderSubtotalRepository $orderSubtotalRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult(
            $this->getOrderSubtotals($context->getFilterValues()->getOne('order')->getValue())
        );
    }

    private function getOrderSubtotals(?int $orderId): array
    {
        if (!$orderId) {
            return [];
        }

        $order = $this->doctrineHelper->getEntity(Order::class, $orderId);
        if (null === $order) {
            return [];
        }

        return $this->orderSubtotalRepository->getOrderSubtotals($order);
    }
}
