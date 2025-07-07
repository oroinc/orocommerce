<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SaleBundle\DependencyInjection\OroSaleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSaleExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroSaleExtension();
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
                        'contact_info_source_display' => ['value' => 'dont_display', 'scope' => 'app'],
                        'contact_details' => ['value' => '', 'scope' => 'app'],
                        'allow_user_configuration' => ['value' => true, 'scope' => 'app'],
                        'available_user_options' => ['value' => [], 'scope' => 'app'],
                        'contact_info_user_option' => ['value' => '', 'scope' => 'app'],
                        'contact_info_manual_text' => ['value' => '', 'scope' => 'app'],
                        'guest_contact_info_text' => ['value' => '', 'scope' => 'app'],
                        'enable_guest_quote' => ['value' => false, 'scope' => 'app'],
                        'quote_frontend_feature_enabled' => ['value' => true, 'scope' => 'app'],
                        'validate_shipping_addresses__backoffice_quote_page' => ['value' => true, 'scope' => 'app'],
                        'enable_quote_project_name' => ['value' => true, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_sale')
        );
    }
}
