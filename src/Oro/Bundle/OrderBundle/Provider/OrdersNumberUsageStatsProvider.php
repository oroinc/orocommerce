<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\AbstractUsageStatsProvider;

/**
 * Usage Stats provider for the number of orders in the system
 */
class OrdersNumberUsageStatsProvider extends AbstractUsageStatsProvider
{
    private ObjectManager $objectManager;
    private OrganizationRestrictionProviderInterface $organizationRestrictionProvider;

    public function __construct(
        ObjectManager $objectManager,
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider
    ) {
        $this->objectManager = $objectManager;
        $this->organizationRestrictionProvider = $organizationRestrictionProvider;
    }

    public function getTitle(): string
    {
        return 'oro.order.usage_stats.orders_number.label';
    }

    public function getTooltip(): string
    {
        return 'oro.order.usage_stats.orders_number.tooltip';
    }

    public function getValue(): ?string
    {
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->objectManager->getRepository(Order::class);
        $queryBuilder = $orderRepository->getSalesOrdersNumberQueryBuilder(
            new \DateTime(date('Y-1-1'), new \DateTimeZone('UTC')),
            new \DateTime('now', new \DateTimeZone('UTC')),
            [
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                OrderStatusesProviderInterface::INTERNAL_STATUS_ARCHIVED,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
            ],
            false,
            'year'
        );

        $this->organizationRestrictionProvider->applyOrganizationRestrictions(
            $queryBuilder
        );

        $result = $queryBuilder
            ->getQuery()
            ->getResult();

        return (string)($result[0]['number'] ?? 0);
    }
}
