<?php

namespace Oro\Bundle\CommerceBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ChartBundle\Model\ChartView;
use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Oro\Bundle\CommerceBundle\Layout\DataProvider\PurchaseVolumeChartDataProvider;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PurchaseVolumeChartDataProviderTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private WebsiteManager&MockObject $websiteManager;
    private UserCurrencyManager&MockObject $userCurrencyManager;
    private LocaleSettings&MockObject $localeSettings;
    private ConfigProvider&MockObject $configProvider;
    private ChartViewBuilder&MockObject $chartViewBuilder;
    private ChartView&MockObject $chartView;

    private PurchaseVolumeChartDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->chartViewBuilder = $this->createMock(ChartViewBuilder::class);
        $this->chartView = $this->createMock(ChartView::class);

        $this->chartViewBuilder->expects(self::any())
            ->method('setArrayData')
            ->with([])
            ->willReturnSelf();

        $this->chartViewBuilder->expects(self::any())
            ->method('setOptions')
            ->with(['name' => PurchaseVolumeChartDataProvider::PURCHASE_VOLUME_CHART, 'settings' => ['xNoTicks' => 0]])
            ->willReturnSelf();

        $this->chartViewBuilder->expects(self::any())
            ->method('getView')
            ->willReturn($this->chartView);

        $this->provider = new PurchaseVolumeChartDataProvider(
            $this->registry,
            $this->websiteManager,
            $this->userCurrencyManager,
            $this->localeSettings,
            $this->configProvider,
            $this->chartViewBuilder,
            ['cancelled']
        );
    }

    public function testGetPurchaseVolumeChartViewNoWebsite(): void
    {
        self::assertSame($this->chartView, $this->provider->getPurchaseVolumeChartView());
    }

    public function testGetPurchaseVolumeChartViewWithUser(): void
    {
        $this->configProvider->expects(self::once())
            ->method('getChartConfig')
            ->with('purchase_volume_chart')
            ->willReturn([]);

        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $user = new User();
        ReflectionUtil::setId($user, 1);

        self::assertSame($this->chartView, $this->provider->getPurchaseVolumeChartView());
    }

    public function testGetPurchaseVolumeChartViewNoCurrency(): void
    {
        $this->configProvider->expects(self::once())
            ->method('getChartConfig')
            ->with('purchase_volume_chart')
            ->willReturn([]);

        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $customer = new Customer();
        ReflectionUtil::setId($customer, 1);
        $customerUser = (new CustomerUser())->setCustomer($customer);

        self::assertSame($this->chartView, $this->provider->getPurchaseVolumeChartView());
    }

    public function testSetInternalStatuses(): void
    {
        self::assertSame(
            ['cancelled'],
            ReflectionUtil::getPropertyValue($this->provider, 'internalStatuses')
        );

        $this->provider->setInternalStatuses(['open']);

        self::assertSame(
            ['open'],
            ReflectionUtil::getPropertyValue($this->provider, 'internalStatuses')
        );
    }
}
