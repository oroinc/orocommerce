<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SaleBundle\Provider\ContactInfoSourceOptionsProvider;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyConfiguration(): void
    {
        $this->assertEquals(
            [
                'settings' => [
                    'resolved' => true,
                    'backend_product_visibility' => [
                        'value' => [Product::INVENTORY_STATUS_IN_STOCK, Product::INVENTORY_STATUS_OUT_OF_STOCK],
                        'scope' => 'app'
                    ],
                    'contact_info_source_display' => [
                        'value' => ContactInfoSourceOptionsProvider::DONT_DISPLAY,
                        'scope' => 'app'
                    ],
                    'contact_details' => ['value' => '', 'scope' => 'app'],
                    'allow_user_configuration' => ['value' => true, 'scope' => 'app'],
                    'available_user_options' => ['value' => [], 'scope' => 'app'],
                    'contact_info_user_option' => ['value' => '', 'scope' => 'app'],
                    'contact_info_manual_text' => ['value' => '', 'scope' => 'app'],
                    'guest_contact_info_text' => ['value' => '', 'scope' => 'app'],
                    'enable_guest_quote' => ['value' => false, 'scope' => 'app'],
                    'quote_frontend_feature_enabled' => ['value' => true, 'scope' => 'app'],
                ]
            ],
            (new Processor())->processConfiguration(new Configuration(), [])
        );
    }
}
