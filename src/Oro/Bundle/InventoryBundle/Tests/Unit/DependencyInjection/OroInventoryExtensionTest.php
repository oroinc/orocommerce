<?php
declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\InventoryBundle\DependencyInjection\OroInventoryExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroInventoryExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroInventoryExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'manage_inventory' => ['value' => false, 'scope' => 'app'],
                        'highlight_low_inventory' => ['value' => false, 'scope' => 'app'],
                        'inventory_threshold' => ['value' => 0, 'scope' => 'app'],
                        'low_inventory_threshold' => ['value' => 0, 'scope' => 'app'],
                        'backorders' => ['value' => false, 'scope' => 'app'],
                        'decrement_inventory' => ['value' => true, 'scope' => 'app'],
                        'minimum_quantity_to_order' => ['value' => null, 'scope' => 'app'],
                        'maximum_quantity_to_order' => ['value' => 100000, 'scope' => 'app'],
                        'hide_labels_past_availability_date' => ['value' => true, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_inventory')
        );

        self::assertSame(
            dirname((new \ReflectionClass(OroInventoryExtension::class))->getFileName())
            . '/../Resources/config/validation_inventory_level.yml',
            $container->getParameter('oro_inventory.validation.config_path')
        );
    }
}
