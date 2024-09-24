<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid;

use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DataGridThemeHelperTest extends \PHPUnit\Framework\TestCase
{
    private const GRID_NAME = 'test-grid-name';
    private const SESSION_KEY = 'frontend-product-grid-view';

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var DataGridThemeHelper */
    private $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->helper = new DataGridThemeHelper(
            $this->requestStack,
            DataGridThemeHelper::VIEW_GRID,
            [DataGridThemeHelper::VIEW_LIST, DataGridThemeHelper::VIEW_GRID, DataGridThemeHelper::VIEW_TILES]
        );
    }

    public function testGetThemeWhenNoCurrentRequest(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->requestStack->expects(self::never())
            ->method('getSession');

        self::assertEquals(DataGridThemeHelper::VIEW_GRID, $this->helper->getTheme(self::GRID_NAME));
    }

    public function testGetThemeWhenNoSession(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(new Request());
        $this->requestStack->expects(self::once())
            ->method('getSession')
            ->willThrowException(new SessionNotFoundException());

        self::assertEquals(DataGridThemeHelper::VIEW_GRID, $this->helper->getTheme(self::GRID_NAME));
    }

    /**
     * @dataProvider getThemeDataProvider
     */
    public function testGetTheme(?string $requestValue, ?string $sessionValue, string $expectedValue): void
    {
        $gridName = self::GRID_NAME;

        $request = new Request();
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->requestStack->expects(self::once())
            ->method('getSession')
            ->willReturn($this->session);

        if ($requestValue) {
            $request->query->set(
                $gridName,
                [DataGridThemeHelper::GRID_THEME_PARAM_NAME => $requestValue]
            );
        } else {
            $this->session->expects(self::once())
                ->method('has')
                ->with(self::SESSION_KEY)
                ->willReturn((bool)$sessionValue);

            if ($sessionValue) {
                $this->session->expects(self::once())
                    ->method('get')
                    ->with(self::SESSION_KEY)
                    ->willReturn($sessionValue);
            }
        }

        self::assertEquals($expectedValue, $this->helper->getTheme($gridName));
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
