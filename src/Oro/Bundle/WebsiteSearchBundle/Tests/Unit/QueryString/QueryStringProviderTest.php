<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\QueryString;

use Oro\Bundle\WebsiteSearchBundle\QueryString\QueryStringProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class QueryStringProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var QueryStringProvider */
    protected $testable;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->testable     = new QueryStringProvider($this->requestStack);
    }

    public function testGetSearchQueryString(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(QueryStringProvider::QUERY_PARAM)
            ->willReturn('some keywords');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $result = $this->testable->getSearchQueryString();

        $this->assertEquals('some keywords', $result);
    }

    public function testGetSearchQueryStringNegative(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(QueryStringProvider::QUERY_PARAM)
            ->willReturn('     ');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $result = $this->testable->getSearchQueryString();

        $this->assertEquals('', $result);
    }

    public function testGetSearchQuerySearchType(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(QueryStringProvider::TYPE_PARAM)
            ->willReturn('some_type');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $result = $this->testable->getSearchQuerySearchType();

        $this->assertEquals('some_type', $result);
    }

    public function testEmptyRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $result = $this->testable->getSearchQuerySearchType();

        $this->assertEquals('', $result);
    }
}
