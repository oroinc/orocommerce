<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\PageTitleProvider;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

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
        $this->contentVariant = $this
            ->getMockBuilder('\Oro\Bundle\WebCatalogBundle\Entity\ContentVariant')
            ->setMethods(['getLandingPageCMSPage', 'getType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTitleProvider = new PageTitleProvider();
        $this->page = new Page();
        $this->page->setTitle('some title');
    }

    public function testGetTitle()
    {
        $this->contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('landing_page_cms_page'));

        $this->contentVariant
            ->expects($this->once())
            ->method('getLandingPageCMSPage')
            ->will($this->returnValue($this->page));

        $this->assertEquals('some title', $this->pageTitleProvider->getTitle($this->contentVariant));
    }
}
