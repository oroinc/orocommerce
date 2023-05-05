<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\LocaleSettings;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContextStack;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocaleSettingsTest extends TestCase
{
    private BaseLocaleSettings|MockObject $inner;

    private FrontendHelper|MockObject $frontendHelper;

    private UserCurrencyManager|MockObject $currencyManager;

    protected LocaleSettings $localeSettings;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(BaseLocaleSettings::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $layoutContextStack = $this->createMock(LayoutContextStack::class);
        $themeManager = $this->createMock(ThemeManager::class);

        $this->localeSettings = new LocaleSettings(
            $this->inner,
            $this->frontendHelper,
            $localizationProvider,
            $this->currencyManager,
            $layoutContextStack,
            $themeManager
        );
    }

    public function testGetCurrency(): void
    {
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->inner->expects(self::once())
            ->method('getCurrency')
            ->willReturn('USD');

        self::assertEquals('USD', $this->localeSettings->getCurrency());

        // Checks local cache.
        self::assertEquals('USD', $this->localeSettings->getCurrency());
    }

    public function testGetCurrencyWithManager(): void
    {
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->inner->expects(self::never())
            ->method('getCurrency');

        $this->currencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn('EUR');

        self::assertEquals('EUR', $this->localeSettings->getCurrency());

        // Checks local cache.
        self::assertEquals('EUR', $this->localeSettings->getCurrency());
    }

    public function testGetCurrencyWithoutManager(): void
    {
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->inner->expects(self::once())
            ->method('getCurrency')
            ->willReturn('PLN');

        $this->currencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn(null);

        self::assertEquals('PLN', $this->localeSettings->getCurrency());

        // Checks local cache.
        self::assertEquals('PLN', $this->localeSettings->getCurrency());
    }
}
