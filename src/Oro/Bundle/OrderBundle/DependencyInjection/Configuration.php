<?php

namespace Oro\Bundle\OrderBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
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
    public const CONFIG_KEY_ENABLE_EXTERNAL_STATUS_MANAGEMENT = 'order_enable_external_status_management';
    public const CONFIG_KEY_NEW_ORDER_INTERNAL_STATUS = 'order_creation_new_internal_order_status';
    public const CONFIG_KEY_PREVIOUSLY_PURCHASED_PERIOD = 'order_previously_purchased_period';
    public const CONFIG_KEY_ENABLE_PURCHASE_HISTORY = 'enable_purchase_history';
    public const string VALIDATE_SHIPPING_ADDRESSES_ON_BACKOFFICE_ORDER_PAGE =
        'validate_shipping_addresses__backoffice_order_page';
    public const string VALIDATE_BILLING_ADDRESSES_ON_BACKOFFICE_ORDER_PAGE =
        'validate_billing_addresses__backoffice_order_page';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'backend_product_visibility' => [
                    'value' => [
                        ExtendHelper::buildEnumOptionId(
                            Product::INVENTORY_STATUS_ENUM_CODE,
                            Product::INVENTORY_STATUS_IN_STOCK
                        ),
                        ExtendHelper::buildEnumOptionId(
                            Product::INVENTORY_STATUS_ENUM_CODE,
                            Product::INVENTORY_STATUS_OUT_OF_STOCK
                        ),
                    ],
                ],
                'frontend_product_visibility' => [
                    'value' => [
                        ExtendHelper::buildEnumOptionId(
                            Product::INVENTORY_STATUS_ENUM_CODE,
                            Product::INVENTORY_STATUS_IN_STOCK
                        ),
                        ExtendHelper::buildEnumOptionId(
                            Product::INVENTORY_STATUS_ENUM_CODE,
                            Product::INVENTORY_STATUS_OUT_OF_STOCK
                        ),
                    ],
                ],
                self::CONFIG_KEY_ENABLE_CANCELLATION => [
                    'value' => false,
                ],
                static::CONFIG_KEY_APPLICABLE_INTERNAL_STATUSES => [
                    'value' => [
                        ExtendHelper::buildEnumOptionId(
                            Order::INTERNAL_STATUS_CODE,
                            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                        )
                    ],
                ],
                static::CONFIG_KEY_TARGET_INTERNAL_STATUS => [
                    'value' => ExtendHelper::buildEnumOptionId(
                        Order::INTERNAL_STATUS_CODE,
                        OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                    )
                ],
                self::CONFIG_KEY_ENABLE_EXTERNAL_STATUS_MANAGEMENT => [
                    'value' => false,
                ],
                static::CONFIG_KEY_NEW_ORDER_INTERNAL_STATUS => [
                    'value' => ExtendHelper::buildEnumOptionId(
                        Order::INTERNAL_STATUS_CODE,
                        OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                    )
                ],
                'order_creation_new_order_owner' => ['value' => null, 'type' => 'string'],
                self::CONFIG_KEY_PREVIOUSLY_PURCHASED_PERIOD => [
                    'value' => 90,
                ],
                self::CONFIG_KEY_ENABLE_PURCHASE_HISTORY => [
                    'value' => false,
                ],
                static::VALIDATE_SHIPPING_ADDRESSES_ON_BACKOFFICE_ORDER_PAGE => [
                    'type' => 'boolean',
                    'value' => true,
                ],
                static::VALIDATE_BILLING_ADDRESSES_ON_BACKOFFICE_ORDER_PAGE => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                'enable_external_order_import' => [ 'type' => 'boolean', 'value' => false]
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
