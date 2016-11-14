<?php

namespace Oro\Bundle\CategoryBundle\Tests\Unit\Provider;

use Oro\Bundle\CatalogBundle\Provider\CategoryTitleProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CategoryTitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryTitleProvider
     */
    protected $categoryTitleProvider;

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
        $this->categoryTitleProvider = new CategoryTitleProvider(
            PropertyAccess::createPropertyAccessor(),
            $localizationHelperLink
        );
    }

    public function testGetTitle()
    {
        $title = (new LocalizedFallbackValue())->setString('some title');
        $category = new Category();
        $category->addTitle($title);

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

        $this->localizationHelper->expects($this->once())
            ->method('getFirstNonEmptyLocalizedValue')
            ->with($category->getTitles())
            ->willReturn($title->getString());

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
