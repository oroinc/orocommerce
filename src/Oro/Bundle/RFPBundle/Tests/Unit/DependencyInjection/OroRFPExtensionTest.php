<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RFPBundle\DependencyInjection\OroRFPExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroRFPExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroRFPExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'feature_enabled' => ['value' => true, 'scope' => 'app'],
                        'frontend_feature_enabled' => ['value' => true, 'scope' => 'app'],
                        'notify_owner_of_customer_user_record' => ['value' => 'always', 'scope' => 'app'],
                        'notify_assigned_sales_reps_of_the_customer' => ['value' => 'always', 'scope' => 'app'],
                        'notify_owner_of_customer' => ['value' => 'always', 'scope' => 'app'],
                        'backend_product_visibility' => [
                            'value' => [
                                'prod_inventory_status.in_stock',
                                'prod_inventory_status.out_of_stock'
                            ],
                            'scope' => 'app'
                        ],
                        'frontend_product_visibility' => [
                            'value' => [
                                'prod_inventory_status.in_stock',
                                'prod_inventory_status.out_of_stock'
                            ],
                            'scope' => 'app'
                        ],
                        'guest_rfp' => ['value' => false, 'scope' => 'app'],
                        'default_guest_rfp_owner' => ['value' => null, 'scope' => 'app'],
                        'enable_rfq_project_name' => ['value' => false, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_rfp')
        );
    }
}
