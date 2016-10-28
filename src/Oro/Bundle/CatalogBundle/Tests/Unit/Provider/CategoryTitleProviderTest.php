<?php

namespace Oro\Bundle\CategoryBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\CatalogBundle\Provider\CategoryTitleProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

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
        $this->categoryTitleProvider = new CategoryTitleProvider(PropertyAccess::createPropertyAccessor());
        $this->category = new Category();
    }

    public function testGetTitle()
    {
        $this->category->addTitle((new LocalizedFallbackValue())->setText('some title'));
        $this->contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->setMethods(['getCatalogPageCategory', 'getType', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('catalog_page_category'));

        $this->contentVariant
            ->expects($this->exactly(2))
            ->method('getCatalogPageCategory')
            ->will($this->returnValue($this->category));

        $this->assertEquals('some title', $this->categoryTitleProvider->getTitle($this->contentVariant));
    }
}
