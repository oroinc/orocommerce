<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\CatalogBundle\Provider\ProductsContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ProductsContentVariantProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductsContentVariantProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->provider = new ProductsContentVariantProvider();
    }

    public function testSupportedClass()
    {
        $this->assertTrue($this->provider->isSupportedClass(Product::class));
        $this->assertFalse($this->provider->isSupportedClass('Test'));
    }

    public function testGetRecordId()
    {
        $array['categoryProductId'] = 1;
        $this->assertEquals($array['categoryProductId'], $this->provider->getRecordId($array));
    }

    public function testGetLocalizedValues()
    {
        $node = $this->getMockBuilder(ContentNodeInterface::class)->getMock();
        $expected = 0;
        $this->assertCount($expected, $this->provider->getLocalizedValues($node));
    }

    public function testGetValues()
    {
        $node = $this->getMockBuilder(ContentNodeInterface::class)->getMock();
        $expected = 0;
        $this->assertCount($expected, $this->provider->getValues($node));
    }
}
