<?php

namespace Oro\Bundle\OrderBundle\DependencyInjection;

use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('oro_order');

        SettingsBuilder::append(
            $rootNode,
            [
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
                'order_automation_enable_cancellation' => [
                    'value' => true,
                ],
                'order_automation_applicable_statuses' => [
                    'value' => [Order::INTERNAL_STATUS_OPEN]
                ],
                'order_automation_target_status' => [
                    'value' => Order::INTERNAL_STATUS_CANCELLED
                ],
                'order_creation_new_internal_order_status' => [
                    'value' => Order::INTERNAL_STATUS_OPEN
                ],
            ]
        );

        return $treeBuilder;
    }
}
