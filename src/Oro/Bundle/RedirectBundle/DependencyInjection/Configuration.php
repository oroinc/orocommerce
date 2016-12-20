<?php

namespace Oro\Bundle\RedirectBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ENABLE_DIRECT_URL = 'enable_direct_url';
    const CANONICAL_URL_TYPE = 'canonical_url_type';
    const SYSTEM_URL = 'system';
    const DIRECT_URL = 'direct';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroRedirectExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                self::ENABLE_DIRECT_URL => ['value' => true],
                self::CANONICAL_URL_TYPE => ['value' => self::SYSTEM_URL]
            ]
        );

        return $treeBuilder;
    }
}
