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
     * @var CategoryTitleProvider
     */
    protected $categoryTitleProvider;

    protected function setUp()
    {
        $this->categoryTitleProvider = new CategoryTitleProvider(PropertyAccess::createPropertyAccessor());
    }

    public function testGetTitle()
    {
        $category = new Category();
        $category->addTitle((new LocalizedFallbackValue())->setText('some title'));

        $contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->setMethods(['getCatalogPageCategory', 'getType'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(CategoryTitleProvider::SUPPORTED_TYPE));

        $contentVariant
            ->expects($this->any())
            ->method('getCatalogPageCategory')
            ->will($this->returnValue($category));

        $this->assertEquals('some title', $this->categoryTitleProvider->getTitle($contentVariant));
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

        $this->assertNull($this->categoryTitleProvider->getTitle($contentVariant));
    }
}
