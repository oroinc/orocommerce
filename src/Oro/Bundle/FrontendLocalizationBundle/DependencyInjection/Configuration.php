<?php

namespace Oro\Bundle\FrontendLocalizationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files
 */
class Configuration implements ConfigurationInterface
{
    public const ROOT_NAME = 'oro_frontend_localization';

    public const SWITCH_LOCALIZATION_BASED_ON_URL = 'switch_localization_based_on_url';
    public const SWITCH_LOCALIZATION_BASED_ON_URL_DEFAULT_VALUE = false;

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NAME);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::SWITCH_LOCALIZATION_BASED_ON_URL => [
                    'value' => self::SWITCH_LOCALIZATION_BASED_ON_URL_DEFAULT_VALUE,
                    'type' => 'boolean'
                ],
            ]
        );

        return $treeBuilder;
    }

    public static function getConfigKeyByName(string $name): string
    {
        return TreeUtils::getConfigKey(self::ROOT_NAME, $name, ConfigManager::SECTION_MODEL_SEPARATOR);
    }
}
