<?php

namespace Oro\Bundle\CategoryBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\CatalogBundle\Provider\CategoryTitleProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class CategoryTitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentVariantInterface
     */
    protected $contentVariant;

    /**
     * @var CategoryTitleProvider
     */
    protected $categoryTitleProvider;

    /**
     * @var Category
     */
    protected $category;

    protected function setUp()
    {
        $this->contentVariant = $this
            ->getMockBuilder('\Oro\Bundle\WebCatalogBundle\Entity\ContentVariant')
            ->setMethods(['getCatalogPageCategory', 'getType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryTitleProvider = new CategoryTitleProvider();
        $this->category = new Category();
        $this->category->addTitle((new LocalizedFallbackValue())->setText('some title'));
    }

    public function testGetTitle()
    {
        $this->contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('catalog_page_category'));

        $this->contentVariant
            ->expects($this->once())
            ->method('getCatalogPageCategory')
            ->will($this->returnValue($this->category));

        $this->assertEquals('some title', $this->categoryTitleProvider->getTitle($this->contentVariant));
    }
}
