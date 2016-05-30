<?php

namespace OroB2B\Bundle\RFPBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\ProductBundle\Entity\Product;

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
                'default_request_status' => ['value' => 'open'],
                'notify_owner_of_account_user_record' => ['value' => 'always'],
                'notify_assigned_sales_reps_of_the_account' => ['value' => 'always'],
                'notify_owner_of_account' => ['value' => 'always'],
                'default_user_for_notifications' => null,
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
                ]
            ]
        );

        return $treeBuilder;
    }
}
