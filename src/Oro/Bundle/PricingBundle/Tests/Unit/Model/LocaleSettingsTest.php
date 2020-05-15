<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\LocaleSettings;

class LocaleSettingsTest extends \PHPUnit\Framework\TestCase
{
    /** @var BaseLocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $inner;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var LocaleSettings */
    protected $localeSettings;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(BaseLocaleSettings::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->localizationManager = $this->createMock(UserLocalizationManager::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);

        $this->localeSettings = new LocaleSettings(
            $this->inner,
            $this->frontendHelper,
            $this->localizationManager,
            $this->currencyManager
        );
    }

    public function testGetCurrency()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->inner->expects($this->once())
            ->method('getCurrency')
            ->willReturn('USD');

        $this->assertEquals('USD', $this->localeSettings->getCurrency());

        // Checks local cache.
        $this->assertEquals('USD', $this->localeSettings->getCurrency());
    }

    public function testGetCurrencyWithManager()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->inner->expects($this->never())
            ->method('getCurrency');

        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('EUR');

        $this->assertEquals('EUR', $this->localeSettings->getCurrency());

        // Checks local cache.
        $this->assertEquals('EUR', $this->localeSettings->getCurrency());
    }

    public function testGetCurrencyWithoutManager()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->inner->expects($this->once())
            ->method('getCurrency')
            ->willReturn('PLN');

        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn(null);

        $this->assertEquals('PLN', $this->localeSettings->getCurrency());

        // Checks local cache.
        $this->assertEquals('PLN', $this->localeSettings->getCurrency());
    }
}
