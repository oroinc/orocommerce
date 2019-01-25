<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\ProductBundle\Layout\DataProvider\SearchProductDataProvider;
use Symfony\Component\Translation\TranslatorInterface;

class SearchProductDataProviderTest extends \PHPUnit\Framework\TestCase
{
    private const SEARCH_KEY = 'key';

    /** @var SearchProductHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $searchProductHandler;

    /** @var SearchProductDataProvider */
    private $searchDataProvider;

    protected function setUp()
    {
        $this->searchProductHandler = self::createMock(SearchProductHandler::class);
        $translator = self::createMock(TranslatorInterface::class);
        $translator
            ->expects(self::any())
            ->method('transChoice')
            ->willReturn(sprintf('Search for "%s"', self::SEARCH_KEY));
        $this->searchDataProvider = new SearchProductDataProvider($this->searchProductHandler, $translator);
    }

    public function testWithSearchStringExists(): void
    {
        $this->searchProductHandler
            ->expects(self::once())
            ->method('getSearchString')
            ->willReturn(self::SEARCH_KEY);
        self::assertEquals(self::SEARCH_KEY, $this->searchDataProvider->getSearchString());
    }

    public function testWithSearchStringNotExists(): void
    {
        $this->searchProductHandler
            ->expects(self::once())
            ->method('getSearchString')
            ->willReturn('');
        self::assertEmpty($this->searchDataProvider->getSearchString());
    }

    public function testGetTitle(): void
    {
        $this->searchProductHandler
            ->expects(self::once())
            ->method('getSearchString')
            ->willReturn(self::SEARCH_KEY);
        self::assertEquals(sprintf('Search for "%s"', self::SEARCH_KEY), $this->searchDataProvider->getTitle());
    }
}
