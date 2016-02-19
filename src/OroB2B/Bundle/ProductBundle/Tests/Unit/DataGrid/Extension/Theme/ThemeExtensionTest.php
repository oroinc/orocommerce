<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\DataGrid\Extension\Theme;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;

use OroB2B\Bundle\ProductBundle\DataGrid\Extension\Theme\ThemeExtension;
use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;

class ThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    const GRID_NAME = 'test-grid-name';

    /**
     * @var ThemeExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DataGridThemeHelper
     */
    protected $themeHelper;

    public function setUp()
    {
        $this->themeHelper = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn(DataGridThemeHelper::VIEW_GRID);

        $this->extension = new ThemeExtension($this->themeHelper);
    }

    public function testIsApplicableFalse()
    {
        $config = DatagridConfiguration::create(['name' => self::GRID_NAME]);
        $actual = $this->extension->isApplicable($config);
        $this->assertEquals(false, $actual);
    }

    public function testIsApplicableTrue()
    {
        $config = DatagridConfiguration::createNamed(ThemeExtension::GRID_NAME, []);
        $actual = $this->extension->isApplicable($config);
        $this->assertEquals(true, $actual);
    }

    public function testVisitMetadata()
    {
        $config = DatagridConfiguration::createNamed(ThemeExtension::GRID_NAME, []);
        $data = MetadataObject::createNamed(self::GRID_NAME, []);

        $this->extension->visitMetadata($config, $data);

        $actual = $data->toArray(['themeOptions']);
        $this->assertEquals(['themeOptions'=> ['rowView'=> DataGridThemeHelper::VIEW_GRID]], $actual);
    }
}
