<?php

namespace Oro\Bundle\OrderBundle\Api\Repository;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
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
        private AuthorizationCheckerInterface $authorizationChecker,
        private DoctrineHelper $doctrineHelper,
        private ObjectNormalizer $objectNormalizer
    ) {
    }

    /**
     * @return OrderSubtotal[]
     */
    public function getOrderSubtotals(int|Order $order): array
    {
        $order = $this->getOrder($order);
        if (!$order) {
            return [];
        }

        $subtotals = $this->totalProcessorProvider->getSubtotals($order);

        $orderSubtotals = [];
        foreach ($subtotals as $number => $subtotal) {
            $orderSubtotals[] = new OrderSubtotal(
                OrderSubtotal::buildOrderSubtotalId($order->getId(), $subtotal->getType(), $number),
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

    public function getNormalizedOrderSubtotals(
        int|Order $order,
        ?EntityDefinitionConfig $config,
        array $normalizationContext
    ): array {
        $orderSubtotals = $this->getOrderSubtotals($order);

        return $this->objectNormalizer->normalizeObjects($orderSubtotals, $config, $normalizationContext);
    }

    private function getOrder(int|Order $order): ?Order
    {
        $order = is_int($order) ? $this->doctrineHelper->getEntity(Order::class, $order) : $order;
        if (!$this->authorizationChecker->isGranted(BasicPermission::VIEW, $order)) {
            return null;
        }

        return $order;
    }
}
