<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Handler;

use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SearchProductHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const KEY = 'search';

    /**
     * @var SearchProductHandler
     */
    private $searchProductHandler;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->searchProductHandler = new SearchProductHandler($this->requestStack);
    }

    public function testSearchKey()
    {
        self::assertEquals(self::KEY, SearchProductHandler::SEARCH_KEY);
    }

    public function testGetSearchStringWithRequestNotExists(): void
    {
        $this->requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        self::assertFalse($this->searchProductHandler->getSearchString());
    }

    public function testGetSearchStringWithRequestSearchKeyNotExists(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects(self::once())
            ->method('get')
            ->willReturn(null);

        $this->requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        self::assertFalse($this->searchProductHandler->getSearchString());
    }

    public function testGetSearchStringWithRequestSearchKeyExists(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects(self::once())
            ->method('get')
            ->willReturn(sprintf(' %s ', self::KEY));

        $this->requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        self::assertEquals('search', $this->searchProductHandler->getSearchString());
    }
}
