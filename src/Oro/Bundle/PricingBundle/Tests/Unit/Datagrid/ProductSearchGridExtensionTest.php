<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\PricingBundle\Datagrid\ProductSearchGridExtension;

class ProductSearchGridExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $source
     * @param string $gridName
     * @param bool $result
     * @dataProvider dataProviderApplicableGrid
     */
    public function testApplicableGrid($source, $gridName, $result)
    {
        $extension = new ProductSearchGridExtension();
        $config = $this->getMockBuilder(DatagridConfiguration::class)->disableOriginalConstructor()->getMock();
        $config->method('getName')->willReturn($gridName);
        $config->method('getDatasourceType')->willReturn($source);
        $this->assertSame($result, $extension->isApplicable($config));
    }

    /**
     * @return array
     */
    public function dataProviderApplicableGrid()
    {
        return [
            [
                'source' => 'orm',
                'gridName' => ProductSearchGridExtension::SUPPORTED_GRID,
                'result' => false,
            ],
            [
                'source' => 'search',
                'gridName' => ProductSearchGridExtension::SUPPORTED_GRID,
                'result' => true,
            ],
            [
                'source' => 'search',
                'gridName' => 'invalid-grid',
                'result' => false,
            ],
        ];
    }

    public function testProccess()
    {
        $extension = new ProductSearchGridExtension();
        $config = DatagridConfiguration::create([]);

        $extension->processConfigs($config);
        $select = $config->offsetGetByPath('[source][query][select]');
        $this->assertCount(1, $select);
        $columnPrice = $config->offsetGetByPath('[columns][price]');
        $this->assertNotEmpty(1, $columnPrice);
        $sortPrice = $config->offsetGetByPath('[sorters][columns][price]');
        $this->assertNotEmpty(1, $sortPrice);
    }
}
