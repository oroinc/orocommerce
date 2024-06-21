<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\PlatformBundle\Provider\UsageStats\AbstractUsageStatsProvider;

/**
 * Usage Stats provider for total sales order amount in the system
 */
class OrdersVolumeUsageStatsProvider extends AbstractUsageStatsProvider
{
    private ObjectManager $objectManager;
    private OrganizationRestrictionProviderInterface $organizationRestrictionProvider;
    private CurrencyProviderInterface $currencyProvider;
    private NumberFormatter $numberFormatter;

    public function __construct(
        ObjectManager $objectManager,
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider,
        CurrencyProviderInterface $currencyProvider,
        NumberFormatter $numberFormatter
    ) {
        $this->objectManager = $objectManager;
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
        return count($this->currencyProvider->getCurrencyList()) === 1;
    }

    public function getValue(): ?string
    {
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->objectManager->getRepository(Order::class);
        $queryBuilder = $orderRepository->getSalesOrdersVolumeQueryBuilder(
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
            'total',
            $this->currencyProvider->getCurrencyList()[0],
            'year'
        );

        $this->organizationRestrictionProvider->applyOrganizationRestrictions(
            $queryBuilder
        );

        $result = $queryBuilder
            ->getQuery()
            ->getResult();

        return $this->numberFormatter->formatCurrency(
            $result[0]['amount'] ?? 0.0
        );
    }
}
