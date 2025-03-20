<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalThemeConfigurationData;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Migrations\Data\AbstractLoadThemeConfiguration;

/**
 * Load Display Price Tiers As Theme Configurations Data for active theme: look at "oro_layout.active_theme"
 */
class LoadDisplayPriceTiersAsThemeConfigurationData extends AbstractLoadThemeConfiguration implements
    DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadGlobalThemeConfigurationData::class
        ];
    }
    #[\Override]
    protected function getConfigManager(): ConfigManager
    {
        return $this->container->get('oro_config.global');
    }

    #[\Override]
    protected function getScopes(): iterable
    {
        return [null];
    }

    #[\Override]
    protected function getThemeConfigurationKeys(): array
    {
        $displayPriceTiersAsKey = ThemeConfiguration::buildOptionKey('product_details', 'display_price_tiers_as');

        return [
            $displayPriceTiersAsKey => 'oro_product.product_details_display_price_tiers_as',
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->init($manager);

        foreach ($this->getScopes() as $scope) {
            $themeConfigurations = $this->getThemeConfigurations($manager, $scope);
            foreach ($themeConfigurations as $themeConfiguration) {
                $frontendTheme = $this->getFrontendTheme($this->configManager, $scope);
                if (!$themeConfiguration || $themeConfiguration->getTheme() !== $frontendTheme) {
                    continue;
                }

                $definition = $this->themeDefinitionBag->getThemeDefinition($frontendTheme);
                $configuration = $this->buildConfigurationFromDefinition($definition, $scope);
                foreach (\array_keys($this->getThemeConfigurationKeys()) as $configurationKey) {
                    if (!$themeConfiguration->getConfigurationOption($configurationKey)) {
                        $themeConfiguration->addConfigurationOption(
                            $configurationKey,
                            $configuration[$configurationKey] ?? null
                        );
                    }
                }
            }
        }

        $manager->flush();
    }

    protected function getThemeConfigurations(ObjectManager $manager, ?object $scope = null): array
    {
        return [$this->getThemeConfiguration($this->configManager, $manager, $scope)];
    }

    #[\Override]
    protected function isApplicable(): bool
    {
        return true;
    }

    #[\Override]
    protected function getFrontendTheme(ConfigManager $configManager, ?object $scope = null): ?string
    {
        return $configManager->get('oro_frontend.frontend_theme', false, false, $scope);
    }
}
