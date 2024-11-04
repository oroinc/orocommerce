<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderCurrencyProvider;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityNotPricedStub;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use Oro\Bundle\WebsiteBundle\Tests\Unit\Stub\WebsiteStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductLineItemsHolderCurrencyProviderTest extends TestCase
{
    private const USD = 'USD';
    private const EUR = 'EUR';

    private UserCurrencyManager|MockObject $userCurrencyManager;

    private WebsiteCurrencyProvider|MockObject $websiteCurrencyProvider;

    private ProductLineItemsHolderCurrencyProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);

        $this->provider = new ProductLineItemsHolderCurrencyProvider(
            $this->userCurrencyManager,
            $this->websiteCurrencyProvider
        );
    }

    public function testGetCurrencyForLineItemsHolderWhenIsCurrencyAwareAndHasCurrency(): void
    {
        $lineItemsHolder = (new EntityStub())
            ->setCurrency(self::USD);

        $this->userCurrencyManager
            ->expects(self::never())
            ->method('getUserCurrency');

        $this->userCurrencyManager
            ->expects(self::never())
            ->method('getDefaultCurrency');

        self::assertEquals(self::USD, $this->provider->getCurrencyForLineItemsHolder($lineItemsHolder));
    }

    public function testGetCurrencyForLineItemsHolderWhenIsCurrencyAwareAndNoCurrencyButGetsUserCurrency(): void
    {
        $lineItemsHolder = new EntityStub();

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn(self::USD);

        $this->userCurrencyManager
            ->expects(self::never())
            ->method('getDefaultCurrency');

        self::assertEquals(self::USD, $this->provider->getCurrencyForLineItemsHolder($lineItemsHolder));
    }

    public function testGetCurrencyForLineItemsHolderWhenIsCurrencyAwareAndNoCurrencyAndNoUserCurrency(): void
    {
        $lineItemsHolder = new EntityStub();

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn('');

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getDefaultCurrency')
            ->willReturn(self::EUR);

        self::assertEquals(self::EUR, $this->provider->getCurrencyForLineItemsHolder($lineItemsHolder));
    }

    public function testGetCurrencyForLineItemsHolderWhenIsWebsiteAwareButGetsUserCurrency(): void
    {
        $lineItemsHolder = new EntityNotPricedStub();

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn(self::USD);

        $this->userCurrencyManager
            ->expects(self::never())
            ->method('getDefaultCurrency');

        self::assertEquals(self::USD, $this->provider->getCurrencyForLineItemsHolder($lineItemsHolder));
    }

    public function testGetCurrencyForLineItemsHolderWhenIsWebsiteAwareAndHasWebsite(): void
    {
        $website = new WebsiteStub(42);
        $lineItemsHolder = (new EntityNotPricedStub())
            ->setWebsite($website);

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn('');

        $this->websiteCurrencyProvider
            ->expects(self::once())
            ->method('getWebsiteDefaultCurrency')
            ->with($website->getId())
            ->willReturn(self::USD);

        $this->userCurrencyManager
            ->expects(self::never())
            ->method('getDefaultCurrency');

        self::assertEquals(self::USD, $this->provider->getCurrencyForLineItemsHolder($lineItemsHolder));
    }
}
