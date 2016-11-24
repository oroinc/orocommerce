<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Provider\PageTitleProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PageTitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PageTitleProvider
     */
    protected $pageTitleProvider;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    protected function setUp()
    {
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $localizationHelperLink = $this->getMockBuilder(ServiceLink::class)
            ->disableOriginalConstructor()
            ->getMock();
        $localizationHelperLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->localizationHelper);
        $this->pageTitleProvider = new PageTitleProvider(
            PropertyAccess::createPropertyAccessor(),
            $localizationHelperLink
        );
    }

    public function testGetTitle()
    {
        $title = (new LocalizedFallbackValue())->setString('some title');
        $page = new Page();
        $page->addTitle($title);

        $contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->setMethods(['getLandingPageCMSPage', 'getType'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(CmsPageContentVariantType::TYPE));

        $contentVariant
            ->expects($this->any())
            ->method('getLandingPageCMSPage')
            ->will($this->returnValue($page));

        $this->localizationHelper->expects($this->once())
            ->method('getFirstNonEmptyLocalizedValue')
            ->with($page->getTitles())
            ->willReturn($title->getString());

        $this->assertEquals('some title', $this->pageTitleProvider->getTitle($contentVariant));
    }

    public function testGetTitleForNonSupportedType()
    {
        $contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('__some_unsupported__'));

        $this->assertNull($this->pageTitleProvider->getTitle($contentVariant));
    }
}
