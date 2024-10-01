<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\AbstractUsageStatsProvider;

/**
 * Usage Stats provider for the number of orders in the system
 */
class OrdersNumberUsageStatsProvider extends AbstractUsageStatsProvider
{
    private ManagerRegistry $doctrine;
    private OrganizationRestrictionProviderInterface $organizationRestrictionProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider
    ) {
        $this->doctrine = $doctrine;
        $this->organizationRestrictionProvider = $organizationRestrictionProvider;
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'oro.order.usage_stats.orders_number.label';
    }

    #[\Override]
    public function getTooltip(): string
    {
        return 'oro.order.usage_stats.orders_number.tooltip';
    }

    #[\Override]
    public function getValue(): ?string
    {
        $queryBuilder = $this->doctrine->getRepository(Order::class)->getSalesOrdersNumberQueryBuilder(
            new \DateTime(date('Y-1-1'), new \DateTimeZone('UTC')),
            null,
            null,
            false,
            'year'
        );
        $this->organizationRestrictionProvider->applyOrganizationRestrictions($queryBuilder);

        $result = $queryBuilder->getQuery()->getResult();

        return (string)($result[0]['number'] ?? 0);
    }
}
