<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\ProductBundle\Layout\DataProvider\SearchProductDataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchProductDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchProductHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $searchProductHandler;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var SearchProductDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->searchProductHandler = $this->createMock(SearchProductHandler::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new SearchProductDataProvider($this->searchProductHandler, $this->translator);
    }

    /**
     * Test that getSearchString returns exactly what the search product handler returns
     */
    public function testGetSearchString(): void
    {
        $testString = 'Some Test String';

        $this->searchProductHandler->expects(self::once())
            ->method('getSearchString')
            ->willReturn($testString);

        self::assertEquals($testString, $this->provider->getSearchString());
    }

    /**
     * Test that getTitle calls transChoice on translator, passing in the correct translation key,
     * the length of the search string and the search string itself as the [%text% => x] parameter.
     * Also test that getTitle returns exactly what transChoice returned.
     */
    public function testGetTitle(): void
    {
        $testString = 'Some Test String';
        $testTitle = 'Translated Title';

        $this->searchProductHandler->expects(self::once())
            ->method('getSearchString')
            ->willReturn($testString);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'oro.product.search.search_title.title',
                ['%count%' => strlen($testString), '%text%' => $testString]
            )
            ->willReturn($testTitle);

        self::assertEquals($testTitle, $this->provider->getTitle());
    }
}
