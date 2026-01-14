<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendLineItemsGrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;

/**
 * Adds save_for_later mass action to frontend-customer-user-shopping-list-edit-grid
 * when saved_for_later feature is enabled.
 */
class SaveForLaterMassActionsListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var array<string> */
    private array $limitedToDatagrids = [];

    /** @var array<string> */
    private array $oldThemes;

    private ThemeConfigurationProvider $themeConfigurationProvider;

    /**
     * @param array<string> $datagrids
     */
    public function setLimitedToDatagrids(array $datagrids): void
    {
        $this->limitedToDatagrids = $datagrids;
    }

    public function setOldThemes(array $oldThemes): void
    {
        $this->oldThemes = $oldThemes;
    }

    public function setThemeConfigurationProvider(ThemeConfigurationProvider $themeConfigurationProvider): void
    {
        $this->themeConfigurationProvider = $themeConfigurationProvider;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $config = $event->getConfig();
        $gridName = $config->getName();

        if (!empty($this->limitedToDatagrids) && !\in_array($gridName, $this->limitedToDatagrids, true)) {
            return;
        }

        if ($this->isOldTheme()) {
            return;
        }

        $config->addMassAction('save_for_later', [
            'type' => 'save-for-later',
            'label' => 'oro.shoppinglist.actions.save_for_later.label',
            'icon' => 'bookmark',
            'entity_name' => 'Oro\Bundle\ShoppingListBundle\Entity\LineItem',
            'acl_resource' => 'oro_shopping_list_frontend_update',
            'data_identifier' => 'lineItem.id',
            'className' => 'btn btn--flat',
            'attributes' => [
                'data-responsive-styler' => '',
                'data-input-widget-options' => [
                    'responsive' => [
                        'mobile-big' => [
                            'classes' => 'dropdown-item text-nowrap'
                        ]
                    ]
                ]
            ],
            'defaultMessages' => [
                'confirm_title' => 'oro.shoppinglist.mass_actions.save_for_later.confirm_title',
                'confirm_content' => 'oro.shoppinglist.mass_actions.save_for_later.confirm_content',
                'confirm_ok' => 'oro.shoppinglist.mass_actions.save_for_later.confirm_ok'
            ],
            'messages' => [
                'success' => 'oro.shoppinglist.mass_actions.save_for_later.success_message'
            ]
        ]);
    }

    private function isOldTheme(): bool
    {
        $themeName = $this->themeConfigurationProvider->getThemeName();

        return \in_array($themeName, $this->oldThemes, true);
    }
}
