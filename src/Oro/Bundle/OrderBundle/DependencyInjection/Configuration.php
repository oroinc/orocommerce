<?php

namespace Oro\Bundle\OrderBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_order';
    public const CONFIG_KEY_ENABLE_CANCELLATION = 'order_automation_enable_cancellation';
    public const CONFIG_KEY_APPLICABLE_INTERNAL_STATUSES = 'order_automation_applicable_statuses';
    public const CONFIG_KEY_TARGET_INTERNAL_STATUS = 'order_automation_target_status';
    public const CONFIG_KEY_NEW_ORDER_INTERNAL_STATUS = 'order_creation_new_internal_order_status';
    public const CONFIG_KEY_PREVIOUSLY_PURCHASED_PERIOD = 'order_previously_purchased_period';
    public const CONFIG_KEY_ENABLE_PURCHASE_HISTORY = 'enable_purchase_history';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'backend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK,
                    ],
                ],
                'frontend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK,
                    ],
                ],
                static::CONFIG_KEY_ENABLE_CANCELLATION => [
                    'value' => false,
                ],
                static::CONFIG_KEY_APPLICABLE_INTERNAL_STATUSES => [
                    'value' => [OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN],
                ],
                static::CONFIG_KEY_TARGET_INTERNAL_STATUS => [
                    'value' => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                ],
                static::CONFIG_KEY_NEW_ORDER_INTERNAL_STATUS => [
                    'value' => OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                ],
                'order_creation_new_order_owner' => ['value' => null, 'type' => 'string'],
                static::CONFIG_KEY_PREVIOUSLY_PURCHASED_PERIOD => [
                    'value' => 90,
                ],
                static::CONFIG_KEY_ENABLE_PURCHASE_HISTORY => [
                    'value' => false,
                ],
            ]
        );

        return $treeBuilder;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getConfigKey($key)
    {
        return self::ROOT_NODE . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
