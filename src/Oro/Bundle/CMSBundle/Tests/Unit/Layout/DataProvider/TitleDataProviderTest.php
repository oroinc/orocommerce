<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Layout\DataProvider\TitleDataProvider;
use Oro\Bundle\CMSBundle\Provider\RequestPageProvider;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\TitleDataProviderInterface;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TitleDataProviderTest extends TestCase
{
    private TitleDataProvider $titleDataProvider;

    private TitleDataProviderInterface&MockObject $decoratedTitleDataProvider;

    private RequestPageProvider&MockObject $requestPageProvider;

    private RequestWebContentVariantProvider&MockObject $requestWebContentVariantProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->decoratedTitleDataProvider = $this->createMock(TitleDataProviderInterface::class);
        $this->requestPageProvider = $this->createMock(RequestPageProvider::class);
        $this->requestWebContentVariantProvider = $this->createMock(
            RequestWebContentVariantProvider::class
        );

        $this->titleDataProvider = new TitleDataProvider(
            $this->decoratedTitleDataProvider,
            $this->requestPageProvider,
            $this->requestWebContentVariantProvider
        );
    }

    public function testThatTitleMethodDelegated(): void
    {
        $this->decoratedTitleDataProvider
            ->expects(self::once())
            ->method('getTitle')
            ->with('test');

        $this->titleDataProvider->getTitle('test');
    }

    /**
     * @dataProvider renderDataProvider
     */
    public function testTitleRenderingForPage(bool $render): void
    {
        $page = new Page();
        $page->setDoNotRenderTitle(!$render);

        $this->requestPageProvider
            ->expects(self::any())
            ->method('getPage')
            ->willReturn($page);

        self::assertEquals($render, $this->titleDataProvider->isRenderTitle());
    }

    public function testThatContentVariantTitleDelegated(): void
    {
        $this->requestWebContentVariantProvider
            ->expects(self::once())
            ->method('getContentVariant')
            ->willReturn(new ContentVariant());

        $this->decoratedTitleDataProvider
            ->expects(self::once())
            ->method('isRenderTitle');

        $this->titleDataProvider->isRenderTitle();
    }

    public function testThatNotContentVariantNotCmsPageTitleDelegated(): void
    {
        $this->decoratedTitleDataProvider
            ->expects(self::once())
            ->method('isRenderTitle');

        $this->titleDataProvider->isRenderTitle();
    }

    private function renderDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
