<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Provider\ProductTitleProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ProductTitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductTitleProvider
     */
    protected $productTitleProvider;

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
        $this->productTitleProvider = new ProductTitleProvider(
            PropertyAccess::createPropertyAccessor(),
            $localizationHelperLink
        );
    }

    public function testGetTitle()
    {
        $name = (new LocalizedFallbackValue())->setString('some title');
        $product = new Product();
        $product->addName($name);

        $contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->setMethods(['getProductPageProduct', 'getType'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(ProductPageContentVariantType::TYPE));

        $contentVariant
            ->expects($this->any())
            ->method('getProductPageProduct')
            ->will($this->returnValue($product));

        $this->localizationHelper->expects($this->once())
            ->method('getFirstNonEmptyLocalizedValue')
            ->with($product->getNames())
            ->willReturn($name->getString());

        $this->assertEquals('some title', $this->productTitleProvider->getTitle($contentVariant));
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

        $this->assertNull($this->productTitleProvider->getTitle($contentVariant));
    }
}
