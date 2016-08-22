<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use OroB2B\Bundle\ProductBundle\Layout\DataProvider\FrontendDatagridRowViewProvider;

class FrontendDatagridRowViewProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DataGridThemeHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeHelper;

    /**
     * @var FrontendDatagridRowViewProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->themeHelper = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper')
            ->disableOriginalConstructor()->getMock();
        $this->provider = new FrontendDatagridRowViewProvider($this->themeHelper);
    }

    public function testGetDataGridTheme()
    {
        $theme = 'tests';
        $this->themeHelper->expects($this->once())
            ->method('getTheme')
            ->with(FrontendDatagridRowViewProvider::FRONTEND_DATAGRID_NAME)
            ->willReturn($theme);
        $this->assertEquals($theme, $this->provider->getDataGridTheme());
    }
}
