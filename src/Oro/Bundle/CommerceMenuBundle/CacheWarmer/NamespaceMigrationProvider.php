<?php

namespace Oro\Bundle\CommerceMenuBundle\CacheWarmer;

use Oro\Bundle\InstallerBundle\CacheWarmer\NamespaceMigrationProviderInterface;

class NamespaceMigrationProvider implements NamespaceMigrationProviderInterface
{
    /**
     * (@inheritdoc}
     */
    public function getConfig()
    {
        $additionConfig = [
            'Oro\Bundle\FrontendNavigationBundle' => 'Oro\Bundle\CommerceMenuBundle',
            'OroFrontendNavigationBundle'         => 'OroCommerceMenuBundle',
            'FrontendNavigationBundle'            => 'CommerceMenuBundle',
        ];

        $changedTranslationKeys = [
            'entity_label',
            'entity_plural_label',
            'active.label',
            'condition.label',
            'id.label',
            'key.label',
            'menu.label',
            'owner_id.label',
            'ownership_type.label',
            'parent_key.label',
            'priority.label',
            'uri.label',
            'image.label',
            'titles.label',
        ];

        foreach ($changedTranslationKeys as $key) {
            $additionConfig["oro.frontendnavigation.menuupdate.$key"] = "oro.commercemenu.menuupdate.$key";
        }

        return $additionConfig;
    }
}
