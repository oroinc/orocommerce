<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Handler;

use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
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

    /**
     * @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $htmlTagHelper;

    protected function setUp()
    {
        $this->requestStack = self::createMock(RequestStack::class);
        $this->htmlTagHelper = self::createMock(HtmlTagHelper::class);
        $this->searchProductHandler = new SearchProductHandler($this->requestStack, $this->htmlTagHelper);
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
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = self::createMock(Request::class);
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
        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = self::createMock(Request::class);
        $request
            ->expects(self::once())
            ->method('get')
            ->willReturn(sprintf(' %s ', self::KEY));

        $this->requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->htmlTagHelper
            ->expects(self::never())
            ->method('escape');

        self::assertEquals('search', $this->searchProductHandler->getSearchString());
    }
}
