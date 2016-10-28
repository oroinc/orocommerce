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
     * @var ContentVariantInterface
     */
    protected $contentVariant;

    /**
     * @var ProductTitleProvider
     */
    protected $productTitleProvider;

    /**
     * @var Product
     */
    protected $product;

    protected function setUp()
    {
        $this->productTitleProvider = new ProductTitleProvider(PropertyAccess::createPropertyAccessor());
        $this->product = new Product();
    }

    public function testGetTitle()
    {
        $this->product->addName((new LocalizedFallbackValue())->setText('some title'));
        $this->contentVariant = $this
            ->getMockBuilder(ContentVariantInterface::class)
            ->setMethods(['getProductPageProduct', 'getType', 'getId', 'getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('product_page_product'));

        $this->contentVariant
            ->expects($this->exactly(2))
            ->method('getProductPageProduct')
            ->will($this->returnValue($this->product));

        $this->assertEquals('some title', $this->productTitleProvider->getTitle($this->contentVariant));
    }
}
