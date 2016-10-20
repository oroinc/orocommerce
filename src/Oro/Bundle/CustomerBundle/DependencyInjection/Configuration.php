<?php

namespace Oro\Bundle\CustomerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroCustomerExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'default_account_owner' => ['type' => 'string', 'value' => 1],
                'anonymous_account_group' => ['type' => 'integer', 'value' => null],
                'registration_allowed' => ['type' => 'boolean', 'value' => true],
                'confirmation_required' => ['type' => 'boolean', 'value' => true],
                'send_password_in_welcome_email' => ['type' => 'boolean', 'value' => false],
            ]
        );

        return $treeBuilder;
    }
}
