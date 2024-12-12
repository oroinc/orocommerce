<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\Provider;

use Oro\Bundle\CheckoutBundle\Layout\Provider\CheckoutThemeBCProvider;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutThemeBCProviderTest extends TestCase
{
    private CheckoutThemeBCProvider $checkoutThemeBCProvider;
    private CurrentThemeProvider|MockObject $currentThemeProvider;
    private ThemeManager|MockObject $themeManager;

    protected function setUp(): void
    {
        $this->currentThemeProvider = $this->createMock(CurrentThemeProvider::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->checkoutThemeBCProvider = new CheckoutThemeBCProvider(
            $this->currentThemeProvider,
            $this->themeManager
        );
    }

    public function testAddTheme(): void
    {
        $this->checkoutThemeBCProvider
            ->addTheme('first_theme')
            ->addTheme('first_theme')
            ->addTheme('second_theme');

        $this->assertEquals(['first_theme', 'second_theme'], $this->checkoutThemeBCProvider->getThemes());
    }

    public function testIsOldTheme(): void
    {
        $this->checkoutThemeBCProvider->addTheme('default_50');

        $this->currentThemeProvider
            ->expects($this->once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager
            ->expects($this->once())
            ->method('themeHasParent')
            ->with('default', ['default_50'])
            ->willReturn(false);

        $this->assertFalse($this->checkoutThemeBCProvider->isOldTheme());
    }
}
