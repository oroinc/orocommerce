<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CheckoutBundle\Datagrid\FrontendShoppingListGridExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

class FrontendShoppingListGridExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const GRIDS = ['test-grid-1', 'test-grid-2'];
    private const ACTIONS = ['test-action-1', 'test-action-2'];

    public function testGetPriority(): void
    {
        $priority = 100;

        $this->assertEquals($priority, $this->createExtension($priority)->getPriority());
    }

    public function testIsApplicable(): void
    {
        $extension = $this->createExtension(100);

        $this->assertTrue($extension->isApplicable(DatagridConfiguration::create(['name' => 'test-grid-1'])));
        $this->assertFalse($extension->isApplicable(DatagridConfiguration::create(['name' => 'test-grid-22'])));
        $this->assertTrue($extension->isApplicable(DatagridConfiguration::create(['name' => 'test-grid-2'])));
    }

    public function testProcessConfigs(): void
    {
        $config = DatagridConfiguration::create(['name' => 'test-grid-1']);

        $this->createExtension(100)->processConfigs($config);
        $this->assertEquals(
            DatagridConfiguration::create(
                [
                    'name' => 'test-grid-1',
                    'actions' => array_combine(self::ACTIONS, [null, null]),
                ]
            ),
            $config
        );

        $this->createExtension(-100)->processConfigs($config);
        $this->assertEquals(DatagridConfiguration::create(['name' => 'test-grid-1', 'actions' => []]), $config);
    }

    private function createExtension(int $priority): FrontendShoppingListGridExtension
    {
        $extension = new FrontendShoppingListGridExtension(self::GRIDS, self::ACTIONS, $priority);
        $extension->setParameters(new ParameterBag());

        return $extension;
    }
}
