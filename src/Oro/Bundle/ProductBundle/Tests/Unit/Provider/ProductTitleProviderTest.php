<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Provider\ProductTitleProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ProductTitleProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductTitleProvider
     */
    protected $productTitleProvider;

    protected function setUp()
    {
        $this->productTitleProvider = new ProductTitleProvider(PropertyAccess::createPropertyAccessor());
    }

    public function testGetTitle()
    {
        $product = new Product();
        $product->addName((new LocalizedFallbackValue())->setText('some title'));

        $contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->setMethods(['getProductPageProduct', 'getType', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(ProductTitleProvider::SUPPORTED_TYPE));

        $contentVariant
            ->expects($this->any())
            ->method('getProductPageProduct')
            ->will($this->returnValue($product));

        $this->assertEquals('some title', $this->productTitleProvider->getTitle($contentVariant));
    }
}
