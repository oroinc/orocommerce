<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides mass actions available for shopping list line items.
 */
class AddLineItemMassActionForOldThemesProvider implements MassActionProviderInterface
{
    public function __construct(
        private readonly MassActionProviderInterface $massActionProvider,
        private readonly ThemeConfigurationProvider $themeConfigurationProvider,
        private readonly TranslatorInterface $translator,
        private array $oldThemes,
    ) {
    }

    public function getActions(): array
    {
        return $this->processActions($this->massActionProvider->getActions());
    }

    public function getFormattedActions(): array
    {
        return $this->processActions($this->massActionProvider->getFormattedActions());
    }

    private function processActions(array $actions): array
    {
        if (!$this->isOldTheme()) {
            return $actions;
        }

        foreach ($actions as $key => $action) {
            if ($action['type'] !== 'addproducts' || !isset($action['entityName'])) {
                continue;
            }

            $actions[$key]['label'] = $this->getFullLabel($action['entityName']);
        }

        return $actions;
    }

    private function isOldTheme(): bool
    {
        $themeName = $this->themeConfigurationProvider->getThemeName();

        return in_array($themeName, $this->oldThemes);
    }

    private function getFullLabel(string $shoppingListName): string
    {
        return $this->translator->trans(
            'oro.shoppinglist.actions.add_to_shopping_list',
            [
                '{{ shoppingList }}' => $shoppingListName
            ]
        );
    }
}
