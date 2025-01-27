<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\OrderBundle\DependencyInjection\OroOrderExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroOrderExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroOrderExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'backend_product_visibility' => [
                            'value' => ['prod_inventory_status.in_stock', 'prod_inventory_status.out_of_stock'],
                            'scope' => 'app'
                        ],
                        'frontend_product_visibility' => [
                            'value' => ['prod_inventory_status.in_stock', 'prod_inventory_status.out_of_stock'],
                            'scope' => 'app'
                        ],
                        'order_automation_enable_cancellation' => ['value' => false, 'scope' => 'app'],
                        'order_automation_applicable_statuses' => [
                            'value' => ['order_internal_status.open'],
                            'scope' => 'app'
                        ],
                        'order_automation_target_status' => [
                            'value' => 'order_internal_status.cancelled',
                            'scope' => 'app'
                        ],
                        'order_enable_external_status_management' => ['value' => false, 'scope' => 'app'],
                        'order_creation_new_internal_order_status' => [
                            'value' => 'order_internal_status.open',
                            'scope' => 'app'
                        ],
                        'order_creation_new_order_owner' => ['value' => null, 'scope' => 'app'],
                        'order_previously_purchased_period' => ['value' => 90, 'scope' => 'app'],
                        'enable_purchase_history' => ['value' => false, 'scope' => 'app'],
                        'validate_shipping_addresses__backoffice_order_page' => ['value' => true, 'scope' => 'app'],
                        'validate_billing_addresses__backoffice_order_page' => ['value' => false, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_order')
        );
    }
}
