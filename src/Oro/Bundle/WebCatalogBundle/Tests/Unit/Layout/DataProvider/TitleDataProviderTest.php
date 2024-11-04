<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\TitleDataProvider;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TitleDataProviderTest extends TestCase
{
    private RequestWebContentVariantProvider&MockObject $requestWebContentVariantProvider;

    private LocalizationHelper&MockObject $localizationHelper;

    private TitleDataProvider $titleDataProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestWebContentVariantProvider = $this->createMock(RequestWebContentVariantProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->titleDataProvider = new TitleDataProvider(
            $this->requestWebContentVariantProvider,
            $this->localizationHelper
        );
    }

    public function testGetTitleFromNode(): void
    {
        $default = 'default';
        $expectedTitle = 'node title';

        $contentNodeTitles = new ArrayCollection();

        $contentNode = $this->createMock(ContentNodeInterface::class);
        $contentNode->expects($this->once())
            ->method('getTitles')
            ->willReturn($contentNodeTitles);
        $contentNode->expects($this->once())
            ->method('isRewriteVariantTitle')
            ->willReturn(true);

        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->once())
            ->method('getNode')
            ->willReturn($contentNode);

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->with($contentNodeTitles)
            ->willReturn($expectedTitle);

        $this->assertEquals($expectedTitle, $this->titleDataProvider->getTitle($default));
    }

    public function testGetTitleFromNodeEmptyTitle(): void
    {
        $default = 'default';

        $contentNodeTitles = new ArrayCollection();

        $contentNode = $this->createMock(ContentNodeInterface::class);
        $contentNode->expects($this->once())
            ->method('getTitles')
            ->willReturn($contentNodeTitles);
        $contentNode->expects($this->once())
            ->method('isRewriteVariantTitle')
            ->willReturn(true);

        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->once())
            ->method('getNode')
            ->willReturn($contentNode);

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->with($contentNodeTitles)
            ->willReturn(null);

        $this->assertEquals($default, $this->titleDataProvider->getTitle($default));
    }

    public function testGetTitleFromNodeWithDisabledRewrite(): void
    {
        $default = 'default';

        $contentNode = $this->createMock(ContentNodeInterface::class);
        $contentNode->expects($this->never())
            ->method('getTitles');
        $contentNode->expects($this->once())
            ->method('isRewriteVariantTitle')
            ->willReturn(false);

        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->once())
            ->method('getNode')
            ->willReturn($contentNode);

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);

        $this->localizationHelper->expects($this->never())
            ->method('getLocalizedValue');

        $this->assertEquals($default, $this->titleDataProvider->getTitle($default));
    }

    public function testGetTitleDefault(): void
    {
        $default = 'default';

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn(null);

        $this->localizationHelper->expects($this->never())
            ->method('getLocalizedValue');

        $this->assertEquals($default, $this->titleDataProvider->getTitle($default));
    }

    /**
     * @dataProvider isRenderTitleDataProvider
     */
    public function testIsRenderTitle(?ContentVariant $contentVariant, bool $expected): void
    {
        $this->requestWebContentVariantProvider
            ->expects(self::once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);

        self::assertEquals($expected, $this->titleDataProvider->isRenderTitle());
    }

    public function isRenderTitleDataProvider(): array
    {
        return [
            'not found content variant' => [
                'contentVariant' => null,
                'expected' => true,
            ],
            'content variant not render title' => [
                'contentVariant' => (new ContentVariant())->setDoNotRenderTitle(true),
                'expected' => false,
            ],
            'content variant render title' => [
                'contentVariant' => new ContentVariant(),
                'expected' => true,
            ]
        ];
    }
}
