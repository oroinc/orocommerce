<?php

namespace Oro\Bundle\ConsentBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration builder for ConsentBundle
 */
class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = OroConsentExtension::ALIAS;
    const CONSENT_FEATURE_ENABLED = 'consent_feature_enabled';
    const ENABLED_CONSENTS = 'enabled_consents';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(static::ROOT_NODE);

        SettingsBuilder::append(
            $rootNode,
            [
                self::CONSENT_FEATURE_ENABLED => ['value' => false, 'type' => 'boolean'],
                self::ENABLED_CONSENTS => ['value' => [], 'type' => 'array'],
            ]
        );

        return $treeBuilder;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getConfigKey($key)
    {
        return sprintf('%s%s%s', self::ROOT_NODE, ConfigManager::SECTION_MODEL_SEPARATOR, $key);
    }
}
