<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\AbstractUsageStatsProvider;

/**
 * Usage Stats provider for total sales order amount in the system
 */
class OrdersVolumeUsageStatsProvider extends AbstractUsageStatsProvider
{
    private ManagerRegistry $doctrine;
    private OrganizationRestrictionProviderInterface $organizationRestrictionProvider;
    private NumberFormatter $numberFormatter;

    public function __construct(
        ManagerRegistry $doctrine,
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider,
        NumberFormatter $numberFormatter
    ) {
        $this->doctrine = $doctrine;
        $this->organizationRestrictionProvider = $organizationRestrictionProvider;
        $this->numberFormatter = $numberFormatter;
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'oro.order.usage_stats.orders_volume.label';
    }

    #[\Override]
    public function getTooltip(): string
    {
        return 'oro.order.usage_stats.orders_volume.tooltip';
    }

    #[\Override]
    public function isApplicable(): bool
    {
        return true;
    }

    #[\Override]
    public function getValue(): ?string
    {
        $queryBuilder = $this->doctrine->getRepository(Order::class)->getSalesOrdersVolumeQueryBuilder(
            new \DateTime(date('Y-1-1'), new \DateTimeZone('UTC')),
            null,
            null,
            false,
            'total',
            null,
            'year'
        );
        $this->organizationRestrictionProvider->applyOrganizationRestrictions($queryBuilder);

        $result = $queryBuilder->getQuery()->getResult();

        return $this->numberFormatter->formatCurrency($result[0]['amount'] ?? 0.0);
    }
}
