<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\PageTitleProvider;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PageTitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentVariantInterface
     */
    protected $contentVariant;

    /**
     * @var PageTitleProvider
     */
    protected $pageTitleProvider;

    /**
     * @var Page
     */
    protected $page;

    protected function setUp()
    {
        $this->pageTitleProvider = new PageTitleProvider(PropertyAccess::createPropertyAccessor());
        $this->page = new Page();
    }

    public function testGetTitle()
    {
        $this->page->setTitle('some title');
        $this->contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->setMethods(['getLandingPageCMSPage', 'getType', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('landing_page_cms_page'));

        $this->contentVariant
            ->expects($this->exactly(2))
            ->method('getLandingPageCMSPage')
            ->will($this->returnValue($this->page));

        $this->assertEquals('some title', $this->pageTitleProvider->getTitle($this->contentVariant));
    }
}
