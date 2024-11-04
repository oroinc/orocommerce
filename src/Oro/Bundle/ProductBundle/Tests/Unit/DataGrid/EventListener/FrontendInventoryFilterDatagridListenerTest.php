<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendInventoryFilterDatagridListener;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class FrontendInventoryFilterDatagridListenerTest extends TestCase
{
    private ConfigManager|MockObject $configManager;
    private TokenStorageInterface|MockObject $tokenStorage;
    private CurrentThemeProvider|MockObject $currentThemeProvider;
    private ThemeManager|MockObject $themeManager;

    private FrontendInventoryFilterDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->currentThemeProvider = $this->createMock(CurrentThemeProvider::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->listener = new FrontendInventoryFilterDatagridListener(
            $this->configManager,
            $this->tokenStorage,
            $this->currentThemeProvider,
            $this->themeManager
        );
    }

    public function testOnBuildBeforeNoInventoryFilter(): void
    {
        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects(self::once())
            ->method('offsetExistByPath')
            ->with('[filters][columns][inventory_status]')
            ->willReturn(false);
        $config->expects(self::never())
            ->method('offsetSetByPath');

        $this->listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $config));
    }

    public function testOnBuildBeforeHideFilterWhenNoToken(): void
    {
        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects(self::exactly(2))
            ->method('offsetExistByPath')
            ->withConsecutive(
                ['[filters][columns][inventory_status]'],
                ['[filters][columns][inventory_status][type]']
            )
            ->willReturnOnConsecutiveCalls(['type' => 'multi-enum'], 'multi-enum');

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                [Configuration::getConfigKeyByName(Configuration::INVENTORY_FILTER_TYPE)],
                [Configuration::getConfigKeyByName(Configuration::INVENTORY_FILTER_ENABLE_FOR_GUESTS)]
            )
            ->willReturnOnConsecutiveCalls(null, false);

        $config->expects(self::once())
            ->method('removeFilter')
            ->with('inventory_status');

        $this->listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $config));
    }

    public function testOnBuildBeforeShowFilterForGuestAndUpdateFilterType(): void
    {
        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects(self::exactly(2))
            ->method('offsetExistByPath')
            ->withConsecutive(
                ['[filters][columns][inventory_status]'],
                ['[filters][columns][inventory_status][type]']
            )
            ->willReturnOnConsecutiveCalls(['type' => 'multi-enum'], 'multi-enum');

        $filterTypeConfigurationValue = 'inventory-switcher';
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                [Configuration::getConfigKeyByName(Configuration::INVENTORY_FILTER_TYPE)],
                [Configuration::getConfigKeyByName(Configuration::INVENTORY_FILTER_ENABLE_FOR_GUESTS)]
            )
            ->willReturnOnConsecutiveCalls($filterTypeConfigurationValue, true);

        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->currentThemeProvider->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with('default', ['default_50', 'default_51'])
            ->willReturn(false);

        $config->expects(self::exactly(2))
            ->method('offsetSetByPath')
            ->withConsecutive(
                ['[filters][columns][inventory_status][type]', $filterTypeConfigurationValue],
                [
                    '[filters][columns][inventory_status][label]',
                    'oro.product.frontend.product_inventory_filter.type.inventory-switcher.label'
                ]
            );
        $config->expects(self::never())
            ->method('removeFilter')
            ->with('inventory_status');

        $this->listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $config));
    }

    public function testOnBuildBeforeOldTheme(): void
    {
        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects(self::exactly(2))
            ->method('offsetExistByPath')
            ->withConsecutive(
                ['[filters][columns][inventory_status]'],
                ['[filters][columns][inventory_status][type]']
            )
            ->willReturnOnConsecutiveCalls(['type' => 'multi-enum'], 'multi-enum');

        $filterTypeConfigurationValue = 'inventory-switcher';
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::INVENTORY_FILTER_TYPE))
            ->willReturnOnConsecutiveCalls($filterTypeConfigurationValue);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->currentThemeProvider->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with('default', ['default_50', 'default_51'])
            ->willReturn(true);

        $config->expects(self::never())
            ->method('offsetSetByPath')
            ->withConsecutive(
                ['[filters][columns][inventory_status][type]', $filterTypeConfigurationValue],
                [
                    '[filters][columns][inventory_status][label]',
                    'oro.product.frontend.product_inventory_filter.type.inventory-switcher.label'
                ]
            );
        $config->expects(self::never())
            ->method('removeFilter')
            ->with('inventory_status');

        $this->listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $config));
    }

    public function testOnBuildBeforeShowFilterForCustomerUserAndUpdateFilterType(): void
    {
        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects(self::exactly(2))
            ->method('offsetExistByPath')
            ->withConsecutive(
                ['[filters][columns][inventory_status]'],
                ['[filters][columns][inventory_status][type]']
            )
            ->willReturnOnConsecutiveCalls(['type' => 'multi-enum'], 'multi-enum');

        $filterTypeConfigurationValue = 'inventory-switcher';
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::INVENTORY_FILTER_TYPE))
            ->willReturnOnConsecutiveCalls($filterTypeConfigurationValue);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->currentThemeProvider->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with('default', ['default_50', 'default_51'])
            ->willReturn(false);

        $config->expects(self::exactly(2))
            ->method('offsetSetByPath')
            ->withConsecutive(
                ['[filters][columns][inventory_status][type]', $filterTypeConfigurationValue],
                [
                    '[filters][columns][inventory_status][label]',
                    'oro.product.frontend.product_inventory_filter.type.inventory-switcher.label'
                ]
            );
        $config->expects(self::never())
            ->method('removeFilter')
            ->with('inventory_status');

        $this->listener->onBuildBefore(new BuildBefore($this->createMock(DatagridInterface::class), $config));
    }
}
