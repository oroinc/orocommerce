<?php

namespace Unit\Datagrid\Provider\MassAction;

use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction\AddLineItemMassActionForOldThemesProvider;
use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction\AddLineItemMassActionProvider;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AddLineItemMassActionForOldThemesProviderTest extends TestCase
{
    private AddLineItemMassActionProvider&MockObject $addLineitemMassAction;

    private ThemeConfigurationProvider&MockObject $themeConfigurationProvider;

    private TranslatorInterface&MockObject $translator;

    private AddLineItemMassActionForOldThemesProvider $addLineitemMassActionForOldThemes;

    protected function setUp(): void
    {
        $this->addLineitemMassAction = $this->createMock(AddLineItemMassActionProvider::class);
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->addLineitemMassActionForOldThemes = new AddLineItemMassActionForOldThemesProvider(
            $this->addLineitemMassAction,
            $this->themeConfigurationProvider,
            $this->translator,
            ['theme_51']
        );
    }

    public function testThatActionsOnNewThemeSkipped(): void
    {
        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn('actual');

        $this->addLineitemMassAction
            ->expects(self::once())
            ->method('getActions')
            ->willReturn([['the same' => 'value']]);

        self::assertEquals([['the same' => 'value']], $this->addLineitemMassActionForOldThemes->getActions());
    }

    public function testActionsProcessing(): void
    {
        $actions = [
            ['label' => 'withoutEntityName', 'type' => 'addproducts'],
            ['label' => 'notCorrectType', 'type' => 'not_valid'],
            ['label' => 'Add to', 'type' => 'addproducts', 'entityName' => 'ShoppingList Name']
        ];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn('theme_51');

        $this->addLineitemMassAction
            ->expects(self::once())
            ->method('getActions')
            ->willReturn($actions);

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->willReturn('Add to ShoppingList Name');

        self::assertEquals(
            [
                ['label' => 'withoutEntityName', 'type' => 'addproducts'],
                ['label' => 'notCorrectType', 'type' => 'not_valid'],
                ['label' => 'Add to ShoppingList Name', 'type' => 'addproducts', 'entityName' => 'ShoppingList Name']
            ],
            $this->addLineitemMassActionForOldThemes->getActions()
        );
    }

    public function testThatGetFormattedActionsMethodAdded(): void
    {
        $this->addLineitemMassAction
            ->expects(self::once())
            ->method('getFormattedActions')
            ->willReturn([]);

        self::assertEquals([], $this->addLineitemMassActionForOldThemes->getFormattedActions());
    }
}
