<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\LocaleSettings;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocaleSettingsTest extends TestCase
{
    private BaseLocaleSettings|MockObject $inner;

    private FrontendHelper|MockObject $frontendHelper;

    private LocalizationProviderInterface|MockObject $localizationProvider;

    private UserCurrencyManager|MockObject $currencyManager;

    private LayoutContextHolder|MockObject $layoutContextHolder;

    private ThemeManager|MockObject $themeManager;

    protected LocaleSettings $localeSettings;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(BaseLocaleSettings::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->layoutContextHolder = $this->createMock(LayoutContextHolder::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->localeSettings = new LocaleSettings(
            $this->inner,
            $this->frontendHelper,
            $this->localizationProvider,
            $this->currencyManager,
            $this->layoutContextHolder,
            $this->themeManager
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
