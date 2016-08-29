<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;

class DataGridThemeHelperTest extends \PHPUnit_Framework_TestCase
{
    const GRID_NAME = 'test-grid-name';

    /**
     * @var DataGridThemeHelper
     */
    protected $helper;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    public function setUp()
    {
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $this->helper = new DataGridThemeHelper($this->requestStack, $this->session);
    }

    public function testGetThemeDefault()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $actual = $this->helper->getTheme(self::GRID_NAME);

        $this->assertEquals(DataGridThemeHelper::VIEW_GRID, $actual);
    }

    /**
     * @dataProvider getThemeDataProvider
     * @param string $requestValue
     * @param string $sessionValue
     * @param string $expectedValue
     */
    public function testGetTheme($requestValue, $sessionValue, $expectedValue)
    {
        $gridName = self::GRID_NAME;

        $request = new Request();
        if ($requestValue) {
            $request->query->set(
                $gridName,
                [DataGridThemeHelper::GRID_THEME_PARAM_NAME => $requestValue]
            );
        } else {
            $this->session->expects($this->once())
                ->method('has')
                ->with(DataGridThemeHelper::SESSION_KEY)
                ->willReturn($sessionValue ? true : false);

            if ($sessionValue) {
                $this->session->expects($this->once())
                    ->method('get')
                    ->with(DataGridThemeHelper::SESSION_KEY)
                    ->willReturn($sessionValue);
            }
        }
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $actualValue = $this->helper->getTheme($gridName);

        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @return array
     */
    public function getThemeDataProvider()
    {
        return [
            [
                'requestValue' => DataGridThemeHelper::VIEW_LIST,
                'sessionValue' => null,
                'expectedValue' => DataGridThemeHelper::VIEW_LIST,
            ],
            [
                'requestValue' => DataGridThemeHelper::VIEW_LIST,
                'sessionValue' => DataGridThemeHelper::VIEW_GRID,
                'expectedValue' => DataGridThemeHelper::VIEW_LIST,
            ],
            [
                'requestValue' => null,
                'sessionValue' => DataGridThemeHelper::VIEW_GRID,
                'expectedValue' => DataGridThemeHelper::VIEW_GRID,
            ],
            [
                'requestValue' => null,
                'sessionValue' => null,
                'expectedValue' => DataGridThemeHelper::VIEW_GRID,
            ],
        ];
    }
}
