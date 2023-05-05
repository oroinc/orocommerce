<?php
declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ShoppingListBundle\DependencyInjection\OroShoppingListExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroShoppingListExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroShoppingListExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'backend_product_visibility' => ['value' => ['in_stock', 'out_of_stock'], 'scope' => 'app'],
                        'availability_for_guests' => ['value' => false, 'scope' => 'app'],
                        'default_guest_shopping_list_owner' => ['value' => null, 'scope' => 'app'],
                        'shopping_list_limit' => ['value' => 0, 'scope' => 'app'],
                        'mass_adding_on_product_listing_enabled' => ['value' => true, 'scope' => 'app'],
                        'create_shopping_list_for_new_guest' => ['value' => false, 'scope' => 'app'],
                        'shopping_lists_max_line_items_per_page' => ['value' => 1000, 'scope' => 'app'],
                        'show_all_in_shopping_list_widget' => ['value' => false, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_shopping_list')
        );
    }
}
