<?php

namespace Oro\Bundle\CommerceBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ChartBundle\Model\ChartView;
use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Provides data for purchase_volume_chart chart on customer dashboard page
 */
class PurchaseVolumeChartDataProvider
{
    public const string PURCHASE_VOLUME_CHART = 'purchase_volume_chart';

    public function __construct(
        private ManagerRegistry $registry,
        private WebsiteManager $websiteManager,
        private DefaultCurrencyProviderInterface $defaultCurrencyProvider,
        private TokenAccessorInterface $tokenAccessor,
        private LocaleSettings $localeSettings,
        private ConfigProvider $configProvider,
        private ChartViewBuilder $chartViewBuilder,
        private array $internalStatuses = []
    ) {
    }

    public function setInternalStatuses(array $internalStatuses): void
    {
        $this->internalStatuses = $internalStatuses;
    }

    public function getPurchaseVolumeChartView(): ChartView
    {
        $data = $this->getPurchaseVolumeChartData();

        $options = \array_merge_recursive(
            ['name' => self::PURCHASE_VOLUME_CHART, 'settings' => ['xNoTicks' => \count($data)]],
            $this->configProvider->getChartConfig('purchase_volume_chart')
        );

        return $this->chartViewBuilder
            ->setArrayData($data)
            ->setOptions($options)
            ->getView();
    }

    private function getPurchaseVolumeChartData(): array
    {
        $websiteId = $this->getWebsiteId();
        if ($websiteId === null) {
            return [];
        }

        $customerId = $this->getCustomerId();
        if ($customerId === null) {
            return [];
        }

        $currency = $this->getCurrency();
        if ($currency === null) {
            return [];
        }

        return $this->registry
            ->getRepository(Order::class)
            ->getOrdersPurchaseVolume(
                $websiteId,
                $customerId,
                $currency,
                'month',
                $this->getDateLimit(),
                $this->internalStatuses
            );
    }

    private function getDateLimit(): \DateTime
    {
        return new \DateTime('-1 year', new \DateTimeZone($this->localeSettings->getTimeZone()));
    }

    private function getCurrency(): ?string
    {
        return $this->defaultCurrencyProvider->getDefaultCurrency() ?? null;
    }

    private function getWebsiteId(): ?int
    {
        return $this->websiteManager->getCurrentWebsite()?->getId();
    }

    private function getCustomerId(): ?int
    {
        $user = $this->tokenAccessor->getUser();

        return $user instanceof CustomerUser ? $user->getCustomer()?->getId() : null;
    }
}
