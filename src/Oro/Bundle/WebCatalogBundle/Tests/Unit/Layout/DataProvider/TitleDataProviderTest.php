<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\TitleDataProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeAwareInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TitleDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var TitleDataProvider
     */
    protected $titleDataProvider;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->titleDataProvider = new TitleDataProvider($this->requestStack, $this->localizationHelper);
    }

    public function testGetTitleFromNode()
    {
        $default = 'default';
        $expectedTitle = 'node title';

        $contentNodeTitles = new ArrayCollection();

        /** @var ContentNodeInterface|\PHPUnit_Framework_MockObject_MockObject $contentNode */
        $contentNode = $this->createMock(ContentNodeInterface::class);
        $contentNode->expects($this->once())
            ->method('getTitles')
            ->willReturn($contentNodeTitles);
        $contentNode->expects($this->once())
            ->method('isRewriteVariantTitle')
            ->willReturn(true);

        /** @var ContentNodeAwareInterface|\PHPUnit_Framework_MockObject_MockObject $contentVariant */
        $contentVariant = $this->createMock(ContentNodeAwareInterface::class);
        $contentVariant->expects($this->once())
            ->method('getNode')
            ->willReturn($contentNode);

        $request = new Request([], [], ['_content_variant' => $contentVariant]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

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

        /** @var ContentNodeInterface|\PHPUnit_Framework_MockObject_MockObject $contentNode */
        $contentNode = $this->createMock(ContentNodeInterface::class);
        $contentNode->expects($this->once())
            ->method('getTitles')
            ->willReturn($contentNodeTitles);
        $contentNode->expects($this->once())
            ->method('isRewriteVariantTitle')
            ->willReturn(true);

        /** @var ContentNodeAwareInterface|\PHPUnit_Framework_MockObject_MockObject $contentVariant */
        $contentVariant = $this->createMock(ContentNodeAwareInterface::class);
        $contentVariant->expects($this->once())
            ->method('getNode')
            ->willReturn($contentNode);

        $request = new Request([], [], ['_content_variant' => $contentVariant]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->with($contentNodeTitles)
            ->willReturn(null);

        $this->assertEquals($default, $this->titleDataProvider->getTitle($default));
    }

    public function testGetTitleFromNodeWithDisabledRewrite()
    {
        $default = 'default';

        /** @var ContentNodeInterface|\PHPUnit_Framework_MockObject_MockObject $contentNode */
        $contentNode = $this->createMock(ContentNodeInterface::class);
        $contentNode->expects($this->never())
            ->method('getTitles');
        $contentNode->expects($this->once())
            ->method('isRewriteVariantTitle')
            ->willReturn(false);

        /** @var ContentNodeAwareInterface|\PHPUnit_Framework_MockObject_MockObject $contentVariant */
        $contentVariant = $this->createMock(ContentNodeAwareInterface::class);
        $contentVariant->expects($this->once())
            ->method('getNode')
            ->willReturn($contentNode);

        $request = new Request([], [], ['_content_variant' => $contentVariant]);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->localizationHelper->expects($this->never())
            ->method('getLocalizedValue');

        $this->assertEquals($default, $this->titleDataProvider->getTitle($default));
    }

    public function testGetTitleDefault()
    {
        $default = 'default';

        $request = new Request([], [], []);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->localizationHelper->expects($this->never())
            ->method('getLocalizedValue');

        $this->assertEquals($default, $this->titleDataProvider->getTitle($default));
    }
}
