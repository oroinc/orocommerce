<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\Layout\DataProvider\FrontendDatagridRowViewProvider;

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
        $this->themeHelper = $this->getMockBuilder('Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper')
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
