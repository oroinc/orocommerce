<?php

namespace OroB2B\Bundle\RFPBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('oro_b2b_rfp');

        SettingsBuilder::append(
            $rootNode,
            [
                'default_request_status' => ['value' => 'open']
            ]
        );

        return $treeBuilder;
    }
}
