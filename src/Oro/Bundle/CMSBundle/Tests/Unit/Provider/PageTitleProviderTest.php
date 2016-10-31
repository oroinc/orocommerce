<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Provider\PageTitleProvider;
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
        $page->setTitle('some title');

        $contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->setMethods(['getLandingPageCMSPage', 'getType', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $contentVariant
            ->expects($this->any())
            ->method('getLandingPageCMSPage')
            ->will($this->returnValue($page));

        $this->assertEquals('some title', $this->pageTitleProvider->getTitle($contentVariant));
    }
}
