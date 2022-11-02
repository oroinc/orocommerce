<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\TitleDataProvider;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class TitleDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestWebContentVariantProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $requestWebContentVariantProvider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var TitleDataProvider */
    private $titleDataProvider;

    protected function setUp(): void
    {
        $this->requestWebContentVariantProvider = $this->createMock(RequestWebContentVariantProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->titleDataProvider = new TitleDataProvider(
            $this->requestWebContentVariantProvider,
            $this->localizationHelper
        );
    }

    public function testGetTitleFromNode()
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

    public function testGetTitleFromNodeEmptyTitle()
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

    public function testGetTitleFromNodeWithDisabledRewrite()
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

    public function testGetTitleDefault()
    {
        $default = 'default';

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn(null);

        $this->localizationHelper->expects($this->never())
            ->method('getLocalizedValue');

        $this->assertEquals($default, $this->titleDataProvider->getTitle($default));
    }
}
