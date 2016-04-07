<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;

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

    public function testDetData()
    {
        $theme = 'tests';
        $this->themeHelper->expects($this->once())
            ->method('getTheme')
            ->with(FrontendDatagridRowViewProvider::FRONTEND_DATAGRID_NAME)
            ->willReturn($theme);
        /** @var ContextInterface $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $this->assertEquals($theme, $this->provider->getData($context));
    }
}
