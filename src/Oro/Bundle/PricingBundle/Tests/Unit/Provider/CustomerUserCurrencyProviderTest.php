<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\PricingBundle\Provider\CustomerUserCurrencyProvider;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CustomerUserCurrencyProviderTest extends TestCase
{
    private WebsiteManager&MockObject $websiteManager;
    private WebsiteCurrencyProvider&MockObject $websiteCurrencyProvider;
    private CurrencyProviderInterface&MockObject $currencyProvider;
    private CustomerUserCurrencyProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);

        $this->provider = new CustomerUserCurrencyProvider(
            $this->websiteManager,
            $this->websiteCurrencyProvider,
            $this->currencyProvider
        );
    }

    public function testGetCurrencyFromUserSettings(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $settings = new CustomerUserSettings($website);
        $settings->setCurrency('EUR');

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects(self::once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn($settings);

        $this->currencyProvider->expects(self::once())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        $this->websiteCurrencyProvider->expects(self::never())
            ->method('getWebsiteDefaultCurrency');

        self::assertSame('EUR', $this->provider->getCustomerUserCurrency($customerUser, $website));
    }

    public function testIgnoresUserSettingsCurrencyIfNotInAllowedList(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $settings = new CustomerUserSettings($website);
        $settings->setCurrency('UAH');

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects(self::once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn($settings);

        $this->currencyProvider->expects(self::once())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        $this->websiteCurrencyProvider->expects(self::once())
            ->method('getWebsiteDefaultCurrency')
            ->with(1)
            ->willReturn('USD');

        self::assertSame('USD', $this->provider->getCustomerUserCurrency($customerUser, $website));
    }

    public function testFallsBackToWebsiteDefaultCurrencyWhenNoUserSettings(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects(self::once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn(null);

        $this->currencyProvider->expects(self::once())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        $this->websiteCurrencyProvider->expects(self::once())
            ->method('getWebsiteDefaultCurrency')
            ->with(1)
            ->willReturn('EUR');

        self::assertSame('EUR', $this->provider->getCustomerUserCurrency($customerUser, $website));
    }

    public function testFallsBackToSystemDefaultWhenWebsiteCurrencyIsEmpty(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects(self::once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn(null);

        $this->currencyProvider->expects(self::once())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        $this->websiteCurrencyProvider->expects(self::once())
            ->method('getWebsiteDefaultCurrency')
            ->with(1)
            ->willReturn('');

        $this->currencyProvider->expects(self::once())
            ->method('getDefaultCurrency')
            ->willReturn('USD');

        self::assertSame('USD', $this->provider->getCustomerUserCurrency($customerUser, $website));
    }

    public function testResolvesWebsiteFromCustomerUserWhenNullPassed(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 2);

        $settings = new CustomerUserSettings($website);
        $settings->setCurrency('EUR');

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects(self::once())
            ->method('getWebsite')
            ->willReturn($website);
        $customerUser->expects(self::once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn($settings);

        $this->websiteManager->expects(self::never())
            ->method('getCurrentWebsite');
        $this->websiteManager->expects(self::never())
            ->method('getDefaultWebsite');

        $this->currencyProvider->expects(self::once())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        self::assertSame('EUR', $this->provider->getCustomerUserCurrency($customerUser));
    }

    public function testResolvesWebsiteFromWebsiteManagerCurrentWhenCustomerUserHasNone(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 3);

        $settings = new CustomerUserSettings($website);
        $settings->setCurrency('USD');

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects(self::once())
            ->method('getWebsite')
            ->willReturn(null);
        $customerUser->expects(self::once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn($settings);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->websiteManager->expects(self::never())
            ->method('getDefaultWebsite');

        $this->currencyProvider->expects(self::once())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        self::assertSame('USD', $this->provider->getCustomerUserCurrency($customerUser));
    }

    public function testResolvesWebsiteFromWebsiteManagerDefaultWhenCurrentIsNull(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 4);

        $settings = new CustomerUserSettings($website);
        $settings->setCurrency('USD');

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects(self::once())
            ->method('getWebsite')
            ->willReturn(null);
        $customerUser->expects(self::once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn($settings);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn(null);
        $this->websiteManager->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($website);

        $this->currencyProvider->expects(self::once())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        self::assertSame('USD', $this->provider->getCustomerUserCurrency($customerUser));
    }
}
