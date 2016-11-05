<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\Page;
use Oro\Bundle\CMSBundle\Provider\PageTitleProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PageTitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PageTitleProvider
     */
    protected $pageTitleProvider;

    protected function setUp()
    {
        $this->pageTitleProvider = new PageTitleProvider(PropertyAccess::createPropertyAccessor());
    }

    public function testGetTitle()
    {
        $page = new Page();
        $page->addTitle((new LocalizedFallbackValue())->setText('some title'));

        $contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->setMethods(['getLandingPageCMSPage', 'getType'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(PageTitleProvider::SUPPORTED_TYPE));

        $contentVariant
            ->expects($this->any())
            ->method('getLandingPageCMSPage')
            ->will($this->returnValue($page));

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
