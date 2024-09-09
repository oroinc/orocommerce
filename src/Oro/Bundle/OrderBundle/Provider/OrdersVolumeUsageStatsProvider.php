<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
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
    private CurrencyProviderInterface $currencyProvider;
    private NumberFormatter $numberFormatter;

    public function __construct(
        ManagerRegistry $doctrine,
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider,
        CurrencyProviderInterface $currencyProvider,
        NumberFormatter $numberFormatter
    ) {
        $this->doctrine = $doctrine;
        $this->organizationRestrictionProvider = $organizationRestrictionProvider;
        $this->currencyProvider = $currencyProvider;
        $this->numberFormatter = $numberFormatter;
    }

    public function getTitle(): string
    {
        return 'oro.order.usage_stats.orders_volume.label';
    }

    public function getTooltip(): string
    {
        return 'oro.order.usage_stats.orders_volume.tooltip';
    }

    public function isApplicable(): bool
    {
        return \count($this->currencyProvider->getCurrencyList()) === 1;
    }

    public function getValue(): ?string
    {
        $queryBuilder = $this->doctrine->getRepository(Order::class)->getSalesOrdersVolumeQueryBuilder(
            new \DateTime(date('Y-1-1'), new \DateTimeZone('UTC')),
            null,
            null,
            false,
            'total',
            $this->currencyProvider->getCurrencyList()[0],
            'year'
        );
        $this->organizationRestrictionProvider->applyOrganizationRestrictions($queryBuilder);

        $result = $queryBuilder->getQuery()->getResult();

        return $this->numberFormatter->formatCurrency($result[0]['amount'] ?? 0.0);
    }
}
