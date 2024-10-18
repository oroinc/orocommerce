<?php

namespace Oro\Bundle\OrderBundle\Api\Repository;

use Oro\Bundle\OrderBundle\Api\Model\OrderSubtotal;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The repository to get order subtotals.
 */
class OrderSubtotalRepository
{
    public function __construct(
        private TotalProcessorProvider $totalProcessorProvider,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    /**
     * @return OrderSubtotal[]
     */
    public function getOrderSubtotals(Order $order): array
    {
        if (!$this->authorizationChecker->isGranted(BasicPermission::VIEW, $order)) {
            return [];
        }

        $orderSubtotals = [];
        $subtotals = $this->totalProcessorProvider->getSubtotals($order);
        foreach ($subtotals as $subtotalNumber => $subtotal) {
            $orderSubtotals[] = new OrderSubtotal(
                $subtotalNumber,
                $subtotal->getType(),
                $subtotal->getLabel(),
                $order->getId(),
                $subtotal->getAmount(),
                $subtotal->getSignedAmount(),
                $subtotal->getCurrency(),
                $subtotal->getPriceList(),
                $subtotal->isVisible(),
                $subtotal->getData() ?? []
            );
        }

        return $orderSubtotals;
    }
}
