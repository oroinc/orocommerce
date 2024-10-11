<?php

namespace Oro\Bundle\OrderBundle\Api\Processor\OrderSubtotal;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\OrderBundle\Api\Repository\OrderSubtotalRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads order subtotals.
 */
class LoadOrderSubtotals implements ProcessorInterface
{
    public function __construct(
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

        $orderId = $context->getFilterValues()->getOne('order')?->getValue();
        $orderSubtotals = $orderId ? $this->orderSubtotalRepository->getOrderSubtotals($orderId) : [];

        $context->setResult($orderSubtotals);
    }
}
