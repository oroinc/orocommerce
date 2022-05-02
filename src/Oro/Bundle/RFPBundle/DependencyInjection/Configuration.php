<?php

namespace Oro\Bundle\RFPBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_rfp';
    const DEFAULT_GUEST_RFP_OWNER = 'default_guest_rfp_owner';

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
                'feature_enabled' => ['value' => true],
                'frontend_feature_enabled' => ['value' => true],
                'notify_owner_of_customer_user_record' => ['value' => 'always'],
                'notify_assigned_sales_reps_of_the_customer' => ['value' => 'always'],
                'notify_owner_of_customer' => ['value' => 'always'],
                'backend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK
                    ]
                ],
                'frontend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK
                    ]
                ],
                'guest_rfp' => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                self::DEFAULT_GUEST_RFP_OWNER => ['type' => 'string', 'value' => null],
            ]
        );

        return $treeBuilder;
    }
}
