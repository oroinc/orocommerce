<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener\FrontendLineItemsGrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendLineItemsGrid\SaveForLaterMassActionsListener;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SaveForLaterMassActionsListenerTest extends TestCase
{
    private ThemeConfigurationProvider&MockObject $themeConfigurationProvider;
    private FeatureChecker&MockObject $featureChecker;
    private DatagridInterface&MockObject $datagrid;
    private SaveForLaterMassActionsListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);

        $this->listener = new SaveForLaterMassActionsListener(
            $this->themeConfigurationProvider,
            ['old_theme']
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('saved_for_later');
    }

    public function testOnBuildBeforeWhenFeatureDisabled(): void
    {
        $config = DatagridConfiguration::create(['name' => 'frontend-customer-user-shopping-list-edit-grid']);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('saved_for_later')
            ->willReturn(false);

        $this->themeConfigurationProvider
            ->expects(self::never())
            ->method('getThemeName');

        $this->listener->onBuildBefore(new BuildBefore($this->datagrid, $config));

        self::assertNull($config->offsetGetByPath('[mass_actions][save_for_later]'));
    }

    public function testOnBuildBeforeWhenOldTheme(): void
    {
        $config = DatagridConfiguration::create(['name' => 'frontend-customer-user-shopping-list-edit-grid']);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('saved_for_later')
            ->willReturn(true);

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn('old_theme');

        $this->listener->onBuildBefore(new BuildBefore($this->datagrid, $config));

        self::assertNull($config->offsetGetByPath('[mass_actions][save_for_later]'));
    }

    public function testOnBuildBeforeWhenNewTheme(): void
    {
        $config = DatagridConfiguration::create(['name' => 'frontend-customer-user-shopping-list-edit-grid']);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('saved_for_later')
            ->willReturn(true);

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn('default');

        $this->listener->onBuildBefore(new BuildBefore($this->datagrid, $config));

        $massAction = $config->offsetGetByPath('[mass_actions][save_for_later]');
        self::assertNotNull($massAction);
        self::assertEquals('save-for-later', $massAction['type']);
        self::assertEquals('oro.shoppinglist.actions.save_for_later.label', $massAction['label']);
        self::assertEquals('bookmark', $massAction['icon']);
        self::assertEquals('Oro\Bundle\ShoppingListBundle\Entity\LineItem', $massAction['entity_name']);
        self::assertEquals('oro_shopping_list_frontend_update', $massAction['acl_resource']);
        self::assertEquals('lineItem.id', $massAction['data_identifier']);
    }

    public function testOnBuildBeforeWhenWrongGridName(): void
    {
        $config = DatagridConfiguration::create(['name' => 'other-grid']);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('saved_for_later')
            ->willReturn(true);

        $this->themeConfigurationProvider
            ->expects(self::never())
            ->method('getThemeName');

        $this->listener->setLimitedToDatagrids(['frontend-customer-user-shopping-list-edit-grid']);
        $this->listener->onBuildBefore(new BuildBefore($this->datagrid, $config));

        self::assertNull($config->offsetGetByPath('[mass_actions][save_for_later]'));
    }

    public function testOnBuildBeforeWhenLimitedToDatagridsEmpty(): void
    {
        $config = DatagridConfiguration::create(['name' => 'any-grid']);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('saved_for_later')
            ->willReturn(true);

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn('default');

        $this->listener->setLimitedToDatagrids([]);
        $this->listener->onBuildBefore(new BuildBefore($this->datagrid, $config));

        $massAction = $config->offsetGetByPath('[mass_actions][save_for_later]');
        self::assertNotNull($massAction);
    }
}
