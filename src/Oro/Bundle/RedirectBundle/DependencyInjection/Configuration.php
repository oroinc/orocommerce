<?php

namespace Oro\Bundle\RedirectBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_redirect';

    public const ENABLE_DIRECT_URL = 'enable_direct_url';
    public const CANONICAL_URL_TYPE = 'canonical_url_type';
    public const USE_LOCALIZED_CANONICAL = 'use_localized_canonical';
    public const REDIRECT_GENERATION_STRATEGY = 'redirect_generation_strategy';

    public const SYSTEM_URL = 'system';
    public const DIRECT_URL = 'direct';

    public const STRATEGY_ALWAYS = 'always';
    public const STRATEGY_NEVER = 'never';
    public const STRATEGY_ASK = 'ask';

    public const CANONICAL_URL_SECURITY_TYPE = 'canonical_url_security_type';
    public const INSECURE = 'insecure';
    public const SECURE = 'secure';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::ENABLE_DIRECT_URL => ['value' => true],
                self::CANONICAL_URL_TYPE => ['value' => self::SYSTEM_URL],
                self::REDIRECT_GENERATION_STRATEGY => ['value' => self::STRATEGY_ASK],
                self::CANONICAL_URL_SECURITY_TYPE => ['value' => self::SECURE],
                self::USE_LOCALIZED_CANONICAL => ['value' => true]
            ]
        );

        return $treeBuilder;
    }

    public static function getConfigKey(string $key): string
    {
        return self::ROOT_NODE . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
