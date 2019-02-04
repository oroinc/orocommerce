<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class merge configuration from your config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    public const DIRECT_EDITING = 'direct_editing';

    /**
     * @var string
     */
    public const LOGIN_PAGE_CSS_FIELD_OPTION = 'login_page_css_field';

    /**
     * @var string
     */
    public const DIRECT_URL_PREFIX = 'landing_page_direct_url_prefix';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroCMSExtension::ALIAS);
        $rootNode
            ->children()
                ->arrayNode(self::DIRECT_EDITING)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode(self::LOGIN_PAGE_CSS_FIELD_OPTION)->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                self::DIRECT_URL_PREFIX => ['value' => '']
            ]
        );

        return $treeBuilder;
    }
}
