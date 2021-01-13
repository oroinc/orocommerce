<?php
declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\InventoryBundle\DependencyInjection\OroInventoryExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroInventoryExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroInventoryExtension());

        $expectedExtensionConfigs = ['oro_inventory'];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);

        $expectedDefinitions = [
            'oro_inventory.importexport.configuration_provider.inventory_level',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAlias(): void
    {
        static::assertEquals('oro_inventory', (new OroInventoryExtension())->getAlias());
    }
}
