<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Provider\ProductTitleProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

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
        $this->contentVariant = $this
            ->getMockBuilder('\Oro\Bundle\WebCatalogBundle\Entity\ContentVariant')
            ->setMethods(['getProductPageProduct', 'getType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productTitleProvider = new ProductTitleProvider();
        $this->product = new Product();
        $this->product->addName((new LocalizedFallbackValue())->setText('some title'));
    }

    public function testGetTitle()
    {
        $this->contentVariant
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('product_page_product'));

        $this->contentVariant
            ->expects($this->once())
            ->method('getProductPageProduct')
            ->will($this->returnValue($this->product));

        $this->assertEquals('some title', $this->productTitleProvider->getTitle($this->contentVariant));
    }
}
