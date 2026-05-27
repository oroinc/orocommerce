<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Provider\CustomerUserCurrencyProvider;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Provider\RfqCurrencyProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RfqCurrencyProviderTest extends TestCase
{
    private CustomerUserCurrencyProvider&MockObject $customerUserCurrencyProvider;

    private WebsiteCurrencyProvider&MockObject $websiteCurrencyProvider;

    private CurrencyProviderInterface&MockObject $currencyProvider;

    private RfqCurrencyProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->customerUserCurrencyProvider = $this->createMock(CustomerUserCurrencyProvider::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);

        $this->provider = new RfqCurrencyProvider(
            $this->customerUserCurrencyProvider,
            $this->websiteCurrencyProvider,
            $this->currencyProvider,
        );
    }

    public function testReturnsCustomerUserCurrencyWhenAvailable(): void
    {
        $customerUser = new CustomerUser();
        $website = new Website();
        ReflectionUtil::setId($website, 1);

        $request = new Request();
        $request->setCustomerUser($customerUser);
        $request->setWebsite($website);

        $this->customerUserCurrencyProvider
            ->expects(self::once())
            ->method('getCustomerUserCurrency')
            ->with($customerUser, $website)
            ->willReturn('EUR');

        $this->websiteCurrencyProvider
            ->expects(self::never())
            ->method('getWebsiteDefaultCurrency');

        $this->currencyProvider
            ->expects(self::never())
            ->method('getDefaultCurrency');

        self::assertSame('EUR', $this->provider->getRfqCurrency($request));
    }

    public function testFallsBackToWebsiteCurrencyWhenNoCustomerUserButWebsiteSet(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 2);

        // No customerUser — provider must use website fallback directly.
        $request = new Request();
        $request->setWebsite($website);

        $this->customerUserCurrencyProvider
            ->expects(self::never())
            ->method('getCustomerUserCurrency');

        $this->websiteCurrencyProvider
            ->expects(self::once())
            ->method('getWebsiteDefaultCurrency')
            ->with(2)
            ->willReturn('GBP');

        $this->currencyProvider
            ->expects(self::never())
            ->method('getDefaultCurrency');

        self::assertSame('GBP', $this->provider->getRfqCurrency($request));
    }

    public function testFallsBackToDefaultCurrencyWhenNoCustomerUserAndNoWebsite(): void
    {
        $request = new Request();

        $this->customerUserCurrencyProvider
            ->expects(self::never())
            ->method('getCustomerUserCurrency');

        $this->websiteCurrencyProvider
            ->expects(self::never())
            ->method('getWebsiteDefaultCurrency');

        $this->currencyProvider
            ->expects(self::once())
            ->method('getDefaultCurrency')
            ->willReturn('USD');

        self::assertSame('USD', $this->provider->getRfqCurrency($request));
    }

    public function testFallsBackToDefaultCurrencyWhenWebsiteCurrencyNotAvailable(): void
    {
        $website = new Website();
        ReflectionUtil::setId($website, 3);

        $request = new Request();
        $request->setWebsite($website);

        $this->customerUserCurrencyProvider
            ->expects(self::never())
            ->method('getCustomerUserCurrency');

        $this->websiteCurrencyProvider
            ->expects(self::once())
            ->method('getWebsiteDefaultCurrency')
            ->with(3)
            ->willReturn('');

        $this->currencyProvider
            ->expects(self::once())
            ->method('getDefaultCurrency')
            ->willReturn('USD');

        self::assertSame('USD', $this->provider->getRfqCurrency($request));
    }
}
