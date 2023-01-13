<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid;

use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DataGridThemeHelperTest extends \PHPUnit\Framework\TestCase
{
    private const GRID_NAME = 'test-grid-name';

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var DataGridThemeHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(SessionInterface::class);

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
     */
    public function testGetTheme(?string $requestValue, ?string $sessionValue, string $expectedValue)
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
                ->willReturn((bool)$sessionValue);

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

    public function getThemeDataProvider(): array
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
            [
                'requestValue' => 'unexpected_value',
                'sessionValue' => null,
                'expectedValue' => DataGridThemeHelper::VIEW_GRID,
            ],
        ];
    }
}
