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
                        'backend_product_visibility' => ['value' => ['in_stock', 'out_of_stock'], 'scope' => 'app'],
                        'frontend_product_visibility' => ['value' => ['in_stock', 'out_of_stock'], 'scope' => 'app'],
                        'order_automation_enable_cancellation' => ['value' => false, 'scope' => 'app'],
                        'order_automation_applicable_statuses' => ['value' => ['open'], 'scope' => 'app'],
                        'order_automation_target_status' => ['value' => 'cancelled', 'scope' => 'app'],
                        'order_creation_new_internal_order_status' => ['value' => 'open', 'scope' => 'app'],
                        'order_creation_new_order_owner' => ['value' => null, 'scope' => 'app'],
                        'order_previously_purchased_period' => ['value' => 90, 'scope' => 'app'],
                        'enable_purchase_history' => ['value' => false, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_order')
        );
    }
}
