<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\DataGrid\Extension\Theme;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\ProductBundle\DataGrid\Extension\Theme\Configuration;
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

    public function testProcessConfigs()
    {
        /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('offsetGetByPath')
            ->with(Configuration::THEME_PATH)
            ->willReturn([DataGridThemeHelper::GRID_THEME_PARAM_NAME => 'row-template']);

        $this->extension->processConfigs($config);
    }
}
