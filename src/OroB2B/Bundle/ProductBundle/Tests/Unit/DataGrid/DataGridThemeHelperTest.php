<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\DataGrid;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;

class DataGridThemeHelperTest extends \PHPUnit_Framework_TestCase
{
    const GRID_NAME = 'test-grid-name';

    /**
     * @var DataGridThemeHelper
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected $requestStack;

    public function setUp()
    {
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $this->helper = new DataGridThemeHelper($this->requestStack);
    }

    public function testGetThemeDefault()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $actual = $this->helper->getTheme(self::GRID_NAME);

        $this->assertEquals(DataGridThemeHelper::VIEW_GRID, $actual);
    }

    public function testGetTheme()
    {
        $request = new Request();
        $request->query->set(
            self::GRID_NAME,
            [DataGridThemeHelper::GRID_THEME_PARAM_NAME => DataGridThemeHelper::VIEW_LIST]
        );

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $actual = $this->helper->getTheme(self::GRID_NAME);

        $this->assertEquals(DataGridThemeHelper::VIEW_LIST, $actual);
    }
}
