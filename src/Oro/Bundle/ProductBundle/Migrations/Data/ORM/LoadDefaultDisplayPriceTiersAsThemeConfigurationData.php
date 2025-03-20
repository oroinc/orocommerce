<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalDefaultThemeConfigurationData;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;

/**
 * Load Display Price Tiers As Theme Configurations Data For Default Theme
 */
class LoadDefaultDisplayPriceTiersAsThemeConfigurationData extends LoadDisplayPriceTiersAsThemeConfigurationData
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            ...parent::getDependencies(),
            LoadGlobalDefaultThemeConfigurationData::class,
        ];
    }

    protected function getFrontendTheme(ConfigManager $configManager, ?object $scope = null): ?string
    {
        return 'default';
    }

    protected function getThemeConfigurations(ObjectManager $manager, ?object $scope = null): array
    {
        return $manager->getRepository(ThemeConfiguration::class)->findBy([
            'theme' => $this->getFrontendTheme($this->configManager, $scope)
        ]);
    }
}
