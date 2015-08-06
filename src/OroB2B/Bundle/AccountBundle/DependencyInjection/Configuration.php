<?php

namespace OroB2B\Bundle\AccountBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\AccountBundle\Form\Type\CatalogVisibilityType;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroB2BAccountExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'default_account_owner' => ['type' => 'string', 'value' => 1],
                'registration_allowed' => ['type' => 'boolean', 'value' => true],
                'confirmation_required' => ['type' => 'boolean', 'value' => true],
                'send_password_in_welcome_email' => ['type' => 'boolean', 'value' => false],
                'category_visibility' => ['value' => CatalogVisibilityType::VISIBILITY_VISIBLE]
            ]
        );

        return $treeBuilder;
    }
}
