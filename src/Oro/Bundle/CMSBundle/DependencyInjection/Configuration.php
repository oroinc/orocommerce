<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DIRECT_URL_PREFIX = 'landing_page_direct_url_prefix';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroCMSExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                self::DIRECT_URL_PREFIX => ['value' => ''],
            ]
        );

        return $treeBuilder;
    }
}
