<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Entity;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;

class RequestProductTest extends AbstractTest
{
    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['request', new Request()],
            ['product', new Product()],
            ['productSku', 'rfp-sku'],
            ['comment', 'comment'],
        ];

        static::assertPropertyAccessors(new RequestProduct(), $properties);

        static::assertPropertyCollections(new RequestProduct(), [
            ['requestProductItems', new RequestProductItem()],
        ]);
    }

    public function testGetEntityIdentifier()
    {
        $request = new RequestProduct();

        $this->setProperty($request, 'id', 321);
        $this->assertEquals(321, $request->getEntityIdentifier());
    }

    /**
     * @depends testProperties
     */
    public function testSetProduct()
    {
        $product        = (new Product())->setSku('rfp-sku');
        $requestProduct = new RequestProduct();

        $this->assertNull($requestProduct->getProductSku());

        $requestProduct->setProduct($product);

        $this->assertEquals($product->getSku(), $requestProduct->getProductSku());
    }

    /**
     * @depends testProperties
     */
    public function testAddRequestProductItem()
    {
        $requestProduct     = new RequestProduct();
        $requestProductItem = new RequestProductItem();

        $this->assertNull($requestProductItem->getRequestProduct());

        $requestProduct->addRequestProductItem($requestProductItem);

        $this->assertEquals($requestProduct, $requestProductItem->getRequestProduct());
    }
}
